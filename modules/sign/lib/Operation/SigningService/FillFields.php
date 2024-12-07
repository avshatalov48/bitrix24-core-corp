<?php

namespace Bitrix\Sign\Operation\SigningService;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Factory;
use Bitrix\Sign\Factory\Field;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\B2e\Provider\ProfileFieldData;
use Bitrix\Sign\Operation\Result\FillFieldsResult;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\RequiredFieldRepository;
use Bitrix\Sign\Service\Api\Document\FieldService;
use Bitrix\Sign\Service\Cache\Memory\Sign\UserCache;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Service\Result\Sign\Block\B2eRequiredFieldsResult;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Type\Member\Role;
use CCrmOwnerType;

final class FillFields implements Contract\Operation
{
	private Factory\Field $fieldFactory;
	private DocumentRepository $documentRepository;
	private Factory\Api\Property\Request\Field\Fill\Value $fieldFillRequestValueFactory;
	/**
	 * @var array<int, <string, array>>
	 */
	private array $fieldSetMemoryCacheByMemberId = [];
	private MemberRepository $memberRepository;
	private BlockRepository $blockRepository;
	private FieldService $apiDocumentFieldService;
	private ProfileProvider $profileProvider;
	private array $legalFieldsByType;
	private UserCache $userCache;
	private MemberService $memberService;
	private readonly int $limit;
	private readonly RequiredFieldRepository $requiredFieldRepository;

	public function __construct(
		private Item\Document $document,
	)
	{
		$this->fieldFactory = new Factory\Field();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->fieldFillRequestValueFactory = new Factory\Api\Property\Request\Field\Fill\Value();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->blockRepository = Container::instance()->getBlockRepository();
		$this->apiDocumentFieldService = Container::instance()->getApiDocumentFieldService();
		$this->profileProvider = Container::instance()->getServiceProfileProvider();
		$this->memberService = Container::instance()->getMemberService();
		$this->userCache = new UserCache();
		$this->memberRepository->setUserCache($this->userCache);
		$this->profileProvider->setCache($this->userCache);
		$this->limit = Storage::instance()->getFieldsFillMembersLimit();
		$this->requiredFieldRepository = Container::instance()->getRequiredFieldRepository();
	}

	public function launch(): FillFieldsResult|Main\Result
	{
		if ($this->document->blankId === null)
		{
			return (new Main\Result())->addError(new Main\Error("Document has no linked blank"));
		}

		if (!$this->getLock())
		{
			return new FillFieldsResult(false);
		}

		$result = $this->processMembers();

		$this->releaseLock();

		return $result;
	}

	private function processMembers(): FillFieldsResult|Main\Result
	{
		$members = $this->memberRepository->listNotConfiguredByDocumentId($this->document->id, $this->limit);
		if ($members->isEmpty())
		{
			return new FillFieldsResult(true, 100);
		}

		$blocks = $this->blockRepository->getCollectionByBlankId($this->document->blankId);
		if (
			Type\DocumentScenario::isB2EScenario($this->document->scenario)
			&& $this->document->scheme === Type\Document\SchemeType::ORDER
		)
		{
			$blocks = $blocks->filterExcludeRole(Type\Member\Role::SIGNER);
		}

		$blocks = $blocks->filterExcludeParty(0);

		$memberFields = new Item\Api\Property\Request\Field\Fill\MemberFieldsCollection();

		foreach ($members as $member)
		{
			$roleBlocks = $blocks->filterByRole($member->role);

			$requestFields = new Item\Api\Property\Request\Field\Fill\FieldCollection();
			foreach ($roleBlocks as $block)
			{
				$fields = $this->fieldFactory->createByBlocks(new Item\BlockCollection($block), $member, $this->document);
				foreach ($fields as $field)
				{
					$this->addFieldValueToRequest($block, $field, $member, $requestFields);
				}
			}

			if (!$requestFields->isEmpty())
			{
				$memberFields->addItem(
					new Item\Api\Property\Request\Field\Fill\MemberFields(
						$member->uid,
						$requestFields,
					),
				);
			}
		}

		$this->appendRequiredFieldsWithoutBlocks($memberFields, $members);

		foreach ($memberFields->toArray() as $memberField)
		{
			$this->checkAndSetTrusted($memberField);
		}

		if (empty($memberFields->toArray()))
		{
			$this->memberRepository->markAsConfigured($members);

			return $this->makeResult($members->count());
		}

		$response = $this->apiDocumentFieldService->fill(
			new Item\Api\Document\Field\FillRequest(
				$this->document->uid,
				$memberFields,
			),
		);

		if (!$response->isSuccess())
		{
			return (new Main\Result())->addErrors($response->getErrors());
		}

		$this->memberRepository->markAsConfigured($members);

		return $this->makeResult($members->count());
	}

	private function addFieldValueToRequest(
		Item\Block $block,
		Item\Field $field,
		Item\Member $member,
		Item\Api\Property\Request\Field\Fill\FieldCollection $requestFields,
	): void
	{
		if ($field->subfields !== null)
		{
			foreach ($field->subfields as $subfield)
			{
				$this->addFieldValueToRequest($block, $subfield, $member, $requestFields);
			}

			return;
		}

		$value = $this->loadFieldValue($block, $field, $member);
		if ($value === null)
		{
			return;
		}
		$fieldValue = $this->fieldFillRequestValueFactory->createByValueItem($value);
		if ($fieldValue === null)
		{
			return;
		}

		$requestFields->addItem(
			new Item\Api\Property\Request\Field\Fill\Field(
				$field->name,
				new Item\Api\Property\Request\Field\Fill\FieldValuesCollection(
					$fieldValue,
				),
				trusted: $value->trusted ?? false,
			),
		);
	}

	private function loadFieldValue(Item\Block $block, Item\Field $field, Item\Member $member): ?Item\Field\Value
	{
		if (BlockCode::isSignature($block->code) || BlockCode::isStamp($block->code))
		{
			$valueFileId = $block->data['fileId'] ?? null;

			return is_int($valueFileId) ? new Item\Field\Value(0, fileId: $valueFileId) : null;
		}
		if (BlockCode::isCommon($block->code))
		{
			$valueString = $block->data['text'] ?? null;

			return is_string($valueString) ? new Item\Field\Value(0, text: $valueString) : null;
		}

		$this->correctBlockCodeByFieldCode($block, $this->document);

		return match (true)
		{
			BlockCode::isReference($block->code) => $this->getCrmReferenceFieldValue($field, $member, $this->document),
			BlockCode::isRequisites($block->code) => $this->getRequisitesFieldValue($field, $member),
			BlockCode::isB2eReference($block->code) => $this->getB2eReferenceFieldValue($block, $field, $member, $this->document),
			default => null,
		};
	}

	private function getCrmReferenceFieldValue(Item\Field $field, Item\Member $member, Item\Document $document): ?Item\Field\Value
	{
		$entityType = \CCrmOwnerType::ResolveName($document->entityTypeId);

		[
			'fieldCode' => $fieldCode,
			'fieldType' => $fieldType,
			'subfieldCode' => $subfieldCode,
		] = NameHelper::parse($field->name);

		[$entityFieldType, $fieldName] = NameHelper::parseFieldCode($fieldCode, $entityType);

		if (
			$fieldType !== FieldType::ADDRESS
			&& $fieldType !== FieldType::LIST
			&& $fieldType !== FieldType::ENUMERATION
		)
		{
			$crmEntityId = $this->getCrmEntityId($member, $document, $entityFieldType);
			if ($crmEntityId !== null)
			{
				$value = CRM::getEntityFieldValue(
					$crmEntityId,
					$fieldCode,
					$document->id,
					$member->presetId,
				);
				if ($value !== null && isset($value['text']))
				{
					return new Item\Field\Value(0, text: $value['text']);
				}
			}
		}

		if (str_starts_with($fieldName, 'UF_CRM_' . $entityType))
		{
			return $this->getDocumentFieldValue($document, $fieldName, $fieldType, $subfieldCode);
		}
		elseif (str_starts_with($fieldName, 'RQ_'))
		{
			return $this->getRequisitesFieldValue($field, $member);
		}
		elseif (
			!empty($subfieldCode)
			&& !empty($fieldType)
			&& $fieldType === FieldType::ADDRESS
		)
		{
			$value = Field::getUserFieldValue($member->entityId, [
				'entityId' => 'CRM_' . $entityFieldType,
				'sourceName' => $fieldName,
				'type' => $fieldType,
				'userFieldId' => $fieldType,
			], $subfieldCode);

			if ($value !== null)
			{
				return new Item\Field\Value(0, text: (string)$value);
			}
		}
		else
		{
			$connector = new Connector\Field\CrmEntity($field, $member);
			$fetchedFieldValue = $connector->fetchFields()->getFirst();

			if ($fetchedFieldValue === null)
			{
				return null;
			}

			if (
				(($fetchedFieldValue->data instanceof Main\Type\DateTime) === true)
				|| is_string($fetchedFieldValue->data)
				|| is_int($fetchedFieldValue->data)
				|| is_float($fetchedFieldValue->data)
			)
			{
				return new Item\Field\Value(0, text: (string)$fetchedFieldValue->data);
			}
		}

		return null;
	}

	private function getRequisitesFieldValue(Item\Field $field, Item\Member $member): ?Item\Field\Value
	{
		[
			'fieldCode' => $fieldCode,
			'subfieldCode' => $subFieldCode,
		] = NameHelper::parse($field->name);

		$fieldsSetValue = $this->getFieldSetValues($member)[$fieldCode] ?? null;
		if ($subFieldCode !== '')
		{
			$value = $this->getFieldSetValues($member)[$fieldCode][$subFieldCode] ?? null;
			if (is_string($value))
			{
				return new Item\Field\Value(fieldId: 0, text: $value);
			}

			return null;
		}

		if (is_string($fieldsSetValue))
		{
			return new Item\Field\Value(fieldId: 0, text: $fieldsSetValue);
		}

		return null;
	}

	/**
	 * @param Item\Member $member
	 *
	 * @return array<string, array>
	 */
	private function getFieldSetValues(Item\Member $member): array
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return [];
		}
		if (!isset($this->fieldSetMemoryCacheByMemberId[$member->id]))
		{
			$this->fieldSetMemoryCacheByMemberId[$member->id] = \Bitrix\Crm\Integration\Sign\Form::getFieldSetValues(
				match ($member->entityType)
				{
					\Bitrix\Sign\Type\Member\EntityType::CONTACT => \CCrmOwnerType::Contact,
					\Bitrix\Sign\Type\Member\EntityType::COMPANY => \CCrmOwnerType::Company,
					default => 0,
				},
				$member->entityId,
				requisitePresetId: $member->presetId,
			);;
		}

		return $this->fieldSetMemoryCacheByMemberId[$member->id];
	}

	private function getB2eReferenceFieldValue(Item\Block $block, Item\Field $field, Item\Member $member, Item\Document $document): ?Item\Field\Value
	{
		['fieldCode' => $fieldCode, 'subfieldCode' => $subfieldCode] = NameHelper::parse($field->name);
		if (str_starts_with($fieldCode, Factory\Field::USER_FIELD_CODE_PREFIX))
		{
			$fieldCode = mb_substr($fieldCode, mb_strlen(Factory\Field::USER_FIELD_CODE_PREFIX));
		}

		if (!$this->profileProvider->isProfileField($fieldCode))
		{
			return $this->getCrmReferenceFieldValue($field, $member, $document);
		}

		$entityId = $block->role === Role::ASSIGNEE
			? $document->representativeId
			: $member->entityId
		;

		$originalValue = $field->type === FieldType::LIST;

		$profileFieldData = $this->profileProvider->loadFieldData(
			userId: $entityId,
			fieldName: $fieldCode,
			subFieldName: $subfieldCode,
			originalValue: $originalValue,
		);
		if ($profileFieldData->value === '')
		{
			return null;
		}

		return new Item\Field\Value(0, text: $profileFieldData->value, trusted: $profileFieldData->isLegal);
	}

	private function correctBlockCodeByFieldCode(Item\Block $block, Item\Document $document): void
	{
		if (isset($block->data['field']))
		{
			[,$fieldName] = NameHelper::parseFieldCode(
				$block->data['field'],
				\CCrmOwnerType::ResolveName($document->entityTypeId),
			);

			if (str_starts_with($fieldName, 'RQ_'))
			{
				$block->code = match($block->code) {
					\Bitrix\Sign\Type\BlockCode::B2E_MY_REFERENCE => \Bitrix\Sign\Type\BlockCode::MY_REQUISITES,
					default => $block->code,
				};
			}
		}
	}

	public function getDocumentFieldValue(
		Item\Document $document,
		string $fieldName,
		string $fieldType,
		string $subfieldCode,
	): Item\Field\Value
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()?->getFactory($document->entityTypeId);
		$value = $factory->getItem($document->entityId)?->get($fieldName);

		if (
			$fieldType === FieldType::ADDRESS
			&& Main\Loader::includeModule('fileman')
			&& Main\Loader::includeModule('location')
		)
		{
			$value = $this->parseAddressSubfieldValue($value, $subfieldCode);
		}

		return new Item\Field\Value(0, text: (string)$value);
	}

	public function parseAddressSubfieldValue(mixed $value, string $subfieldCode): string
	{
		[,,$addressId] = AddressType::parseValue($value);
		if (!$addressId)
		{
			return '';
		}

		/** @var Address $address */
		$address = Address::load($addressId);
		if (!$address)
		{
			return '';
		}

		if (!empty($subfieldCode))
		{
			$value = $address->getFieldValue(FieldType::ADDRESS_SUBFIELD_MAP[$subfieldCode]);
		}
		else
		{
			$value = $address->toString(
				FormatService::getInstance()->findDefault(LANGUAGE_ID),
				\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
			);
		}

		return (string)$value;
	}

	private function getCrmEntityId(Item\Member $member, Item\Document $document, string $entityTypeName): ?int
	{
		if (
			$entityTypeName === CCrmOwnerType::CompanyName
			&& $member->entityType === \Bitrix\Sign\Type\Member\EntityType::COMPANY
		)
		{
			return $member->entityId;
		}
		elseif (
			$entityTypeName === CCrmOwnerType::ContactName
			&& $member->entityType === \Bitrix\Sign\Type\Member\EntityType::CONTACT
		)
		{
			return $member->entityId;
		}
		elseif (in_array($entityTypeName, [CCrmOwnerType::SmartB2eDocumentName, CCrmOwnerType::SmartDocumentName], true))
		{
			return $document->id;
		}

		return null;
	}

	private function checkAndSetTrusted(Item\Api\Property\Request\Field\Fill\MemberFields $memberFields): void
	{
		foreach ($memberFields->fields->toArray() as $field)
		{
			if (!$field->trusted)
			{
				return;
			}
		}

		$memberFields->trusted = true;
	}

	private function appendRequiredFieldsWithoutBlocks(
		Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $memberFieldsCollection,
		Item\MemberCollection $members,
	): void
	{
		if (!Type\DocumentScenario::isB2EScenario($this->document->scenario))
		{
			return;
		}

		$requiredFields = $this->getRequiredFields();
		if ($requiredFields === null)
		{
			return;
		}

		$blockFieldNames = $this->getFieldNames($memberFieldsCollection);
		foreach ($requiredFields as $requiredField)
		{
			$field = $this->fieldFactory->createByRequired($this->document, $members, $requiredField);
			if ($field instanceof Item\Field && !isset($blockFieldNames[$field->name]))
			{
				$this->appendFieldsWithoutBlocksValues($field, $requiredField, $members, $memberFieldsCollection);
			}
		}
	}

	private function getRequiredFields(): ?Item\B2e\RequiredFieldsCollection
	{
		$collection = $this->getRequiredFieldsFromDb();

		return $collection->isEmpty() ? $this->getRequiredFieldsFromApi() : $collection;
	}

	private function getRequiredFieldsFromDb(): Item\B2e\RequiredFieldsCollection
	{
		return $this->requiredFieldRepository
			->listByDocumentId($this->document->id)
			->convertToRequiredFieldCollection();
	}

	private function getRequiredFieldsFromApi(): ?Item\B2e\RequiredFieldsCollection
	{
		$apiResult = Container::instance()
							  ->getApiB2eProviderFieldsService()
							  ->loadRequiredFields($this->document->companyUid)
		;

		return $apiResult instanceof B2eRequiredFieldsResult ? $apiResult->collection : null;
	}

	/**
	 * Get all field names in blocks as key map
	 *
	 * @param Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $memberFieldsCollection
	 *
	 * @return array<string, string>
	 */
	private function getFieldNames(Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $memberFieldsCollection): array
	{
		$fieldNames = [];
		foreach ($memberFieldsCollection->toArray() as $item)
		{
			foreach ($item->fields->toArray() as $field)
			{
				$name = $field->name;
				$fieldNames[$name] = $name;
			}
		}

		return $fieldNames;
	}

	private function appendFieldsWithoutBlocksValues(
		Item\Field $field,
		Item\B2e\RequiredField $requiredField,
		Item\MemberCollection $members,
		Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $memberFieldsCollection,
	): void
	{
		$legalField = $this->getLegalInfoFieldByType($requiredField->type);
		if (!$legalField)
		{
			return;
		}

		foreach ($members->filterByRole($requiredField->role) as $member)
		{
			$userId = $this->memberService->getUserIdForMember($member, $this->document);
			$profileFieldData = $this->profileProvider->loadFieldData($userId, $legalField->name);
			if (!$profileFieldData->value)
			{
				continue;
			}
			$requestField = $this->makeRequestFieldByValue($field, $profileFieldData);
			$existedMemberFields = $this->getExistedMemberFields($memberFieldsCollection, $member->uid);
			if ($existedMemberFields)
			{
				$existedMemberFields->fields->addItem($requestField);
			}
			else
			{
				$requestFields = new Item\Api\Property\Request\Field\Fill\FieldCollection($requestField);
				$memberFields = new Item\Api\Property\Request\Field\Fill\MemberFields($member->uid, $requestFields);
				$memberFieldsCollection->addItem($memberFields);
			}
		}
	}

	private function makeRequestFieldByValue(
		Item\Field $field,
		ProfileFieldData $profileFieldData,
	): Item\Api\Property\Request\Field\Fill\Field
	{
		$fieldValue = new Item\Api\Property\Request\Field\Fill\Value\StringFieldValue($profileFieldData->value);
		$requestValues = new Item\Api\Property\Request\Field\Fill\FieldValuesCollection($fieldValue);

		return new Item\Api\Property\Request\Field\Fill\Field($field->name, $requestValues, $profileFieldData->isLegal);
	}

	private function getExistedMemberFields(
		Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $memberFieldsCollection,
		string $memberUid,
	): ?Item\Api\Property\Request\Field\Fill\MemberFields
	{
		foreach ($memberFieldsCollection->toArray() as $memberFields)
		{
			if ($memberFields->memberId === $memberUid)
			{
				return $memberFields;
			}
		}

		return null;
	}

	private function getLegalInfoFieldByType(string $type): ?Item\B2e\LegalInfoField
	{
		if (!isset($this->legalFieldsByType))
		{
			$this->legalFieldsByType = [];
			foreach ((new LegalInfoProvider())->getFieldsItems() as $field)
			{
				$this->legalFieldsByType[$field->type] = $field;
			}
		}

		return $this->legalFieldsByType[$type] ?? null;
	}

	private function getLock(): bool
	{
		return Main\Application::getConnection()
			->lock($this->getLockName())
		;
	}

	private function releaseLock(): bool
	{
		return Main\Application::getConnection()
		  ->unlock($this->getLockName())
		;
	}

	private function getLockName(): string
	{
		return "member_fields_{$this->document->uid}";
	}

	private function makeResult(int $processedMembersCount): FillFieldsResult
	{
		if ($processedMembersCount < $this->limit)
		{
			return new FillFieldsResult(true);
		}

		$notConfigured = $this->memberRepository->countNotConfiguredByDocumentId($this->document->id);

		return new FillFieldsResult(!$notConfigured);
	}
}

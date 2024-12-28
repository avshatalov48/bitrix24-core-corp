<?php

namespace Bitrix\Sign\Factory;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;
use Bitrix\Sign\Connector\MemberConnectorFactory;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation\GetRequiredFieldsWithCache;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkFieldService;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;
use Bitrix\Sign\Service\Result\Sign\Block\B2eRequiredFieldsResult;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\Field\ConnectorType;
use Bitrix\Sign\Type\Field\EntityType;
use Bitrix\Sign\Type\FieldType;

final class Field
{
	public const USER_FIELD_CODE_PREFIX = 'USER_';

	private const BLOCK_CODE_TO_FIELD_TYPE_MAP = [
		BlockCode::DATE => FieldType::STRING,
		BlockCode::TEXT => FieldType::STRING,
		BlockCode::NUMBER => FieldType::STRING,
		BlockCode::MY_STAMP => FieldType::STAMP,
		BlockCode::STAMP => FieldType::STAMP,
		BlockCode::MY_SIGN => FieldType::SIGNATURE,
		BlockCode::SIGN => FieldType::SIGNATURE,
	];

	private const NOT_REQUIRED_FIELD_TYPES = [
		FieldType::PATRONYMIC,
		FieldType::POSITION,
	];

	private const ADDRESS_SUBFIELD_CODES = [
		'ADDRESS_1',
		'ADDRESS_2',
		'CITY',
		'POSTAL_CODE',
		'REGION',
		'PROVINCE',
		'COUNTRY',
	];
	private const REQUIRED_ADDRESS_SUBFIELDS = [
		'ADDRESS_1',
		'CITY',
		'POSTAL_CODE',
	];

	private const SNILS_FIELD_CODE = 'UF_LEGAL_SNILS';

	private MemberConnectorFactory $memberConnectorFactory;
	private readonly BlockRepository $blockRepository;
	private readonly FieldValue $fieldValueFactory;
	private readonly HcmLinkFieldService $hcmLinkFieldService;
	private readonly \Bitrix\Sign\Blanks\Block\Factory $blockFactory;

	public function __construct(
		?BlockRepository $blockRepository = null,
		?HcmLinkFieldService $hcmLinkFieldService = null,
		?\Bitrix\Sign\Blanks\Block\Factory $blockFactory = null,
	)
	{
		$this->memberConnectorFactory = new MemberConnectorFactory();
		$this->blockRepository = $blockRepository ?? Container::instance()->getBlockRepository();
		$this->fieldValueFactory = new FieldValue();
		$this->hcmLinkFieldService = $hcmLinkFieldService ?? Container::instance()->getHcmLinkFieldService();
		$this->blockFactory = $blockFactory ?? new \Bitrix\Sign\Blanks\Block\Factory();
	}

	/**
	 * @param int $userId
	 * @param array{entityId: string, sourceName: string, type: string, userFieldId: string} $field
	 * @param string $subFieldName
	 * @param bool $originalValue
	 *
	 * @return mixed
	 */
	public static function getUserFieldValue(int $userId, array $field, string $subFieldName = '', bool $originalValue = false): mixed
	{
		global $USER_FIELD_MANAGER;

		$result = $USER_FIELD_MANAGER->GetUserFieldValue(
			entity_id: $field['entityId'],
			field_id: $field['sourceName'],
			value_id: $userId,
			LANG: LANGUAGE_ID,
		);

		if (empty($result) || $originalValue)
		{
			return $result;
		}

		if ($field['type'] === 'enumeration')
		{
			$enumResultDb = \CUserFieldEnum::GetList([], [
				'USER_FIELD_ID' => $field['userFieldId'],
				'ID' => $result,
			]);

			$userFieldCollection = [];
			while ($enumResult = $enumResultDb->Fetch())
			{
				$userFieldCollection[] = $enumResult['VALUE'];
			}

			return implode(', ', $userFieldCollection);
		}
		elseif (
			$field['type'] === 'address'
			&& Main\Loader::includeModule('fileman')
			&& Main\Loader::includeModule('location')
		)
		{
			[,, $addressId] = AddressType::parseValue($result);

			if (empty($addressId))
			{
				return '';
			}
			else
			{
				/** @var Address $address */
				$address = Address::load($addressId);
				if (!$address)
				{
					return '';
				}

				if (!empty($subFieldName))
				{
					return $address->getFieldValue(FieldType::ADDRESS_SUBFIELD_MAP[$subFieldName]);
				}
				else
				{
					return $address->toString(
						FormatService::getInstance()->findDefault(LANGUAGE_ID),
						\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
					);
				}
			}
		}

		return $result;
	}

	public function createByBlocks(
		Item\BlockCollection $blocks,
		?Item\Member $member,
		?Item\Document $document = null,
	): Item\FieldCollection
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return new Item\FieldCollection();
		}

		$documentRepository = Container::instance()->getDocumentRepository();
		if ($member !== null && $document === null)
		{
			$document = $documentRepository->getById($member->documentId);
		}

		$registeredFields = new Item\FieldCollection();
		foreach ($blocks as $block)
		{
			$fields = $this->createFieldsByBlock($block, $registeredFields, $member, $document);
			$registeredFields->mergeFieldsWithNoneIncludedName($fields);
		}

		return $registeredFields;
	}

	private function createFieldsByBlock(
		Item\Block $block,
		Item\FieldCollection $registeredFields,
		?Item\Member $member,
		?Item\Document $document,
	): Item\FieldCollection
	{
		$fieldType = self::BLOCK_CODE_TO_FIELD_TYPE_MAP[$block->code] ?? null;

		if (BlockCode::isCommon($block->code))
		{
			$party = $member?->party ?? 0;
			$fieldName = NameHelper::create($block->code, $fieldType, $party);

			return new Item\FieldCollection(
				new Item\Field(
					0,
					$party,
					$fieldType,
					$fieldName,
					label: null, // simple blocks doesnt contains label because it not include in form
				),
			);
		}
		if ($member === null || $document === null)
		{
			return new Item\FieldCollection();
		}

		if ($fieldType === null)
		{
			return match ($block->code)
			{
				BlockCode::REFERENCE, BlockCode::MY_REFERENCE => $this->createCrmReferenceFields(
					$block,
					$registeredFields,
					$member,
				),
				BlockCode::REQUISITES, BlockCode::MY_REQUISITES => $this->createRequisiteFields(
					$block,
					$member,
				),
				BlockCode::B2E_REFERENCE, BlockCode::B2E_MY_REFERENCE => $this->createB2eReferenceFields(
					$block,
					$member,
					$document,
					$registeredFields,
				),
				BlockCode::EMPLOYEE_DYNAMIC => $this->createDynamicMemberFields(
					$block,
					$member,
					$document,
					$registeredFields,
				),
				BlockCode::B2E_HCMLINK_REFERENCE => $this->createHcmLinkFields(
					$block,
					$member,
					$document,
					$registeredFields,
				),
			};
		}

		if (BlockCode::isSignature($block->code))
		{
			$signField = $registeredFields->findFirst(
				fn(Item\Field $field) => $field->type === FieldType::SIGNATURE && $field->party === $member->party,
			);
			if ($signField !== null)
			{
				return new Item\FieldCollection($signField);
			}
		}
		elseif (BlockCode::isStamp($block->code))
		{
			$stampField = $registeredFields->findFirst(
				fn(Item\Field $field) => $field->type === FieldType::STAMP && $field->party === $member->party,
			);
			if ($stampField !== null)
			{
				return new Item\FieldCollection($stampField);
			}
		}
		elseif ($block->code === BlockCode::NUMBER)
		{
			$numField = $registeredFields->findFirst(
				fn(Item\Field $field) => NameHelper::parse($field->name)['blockCode'] === BlockCode::NUMBER,
			);
			if ($numField !== null)
			{
				return new Item\FieldCollection($numField);
			}
		}

		$fieldName = NameHelper::create($block->code, $fieldType, $member->party);

		return new Item\FieldCollection(
			new Item\Field(
				0,
				$member->party,
				$fieldType,
				$fieldName,
				label: null, // simple blocks doesnt contains label because it not include in form
			),
		);
	}

	private function createCrmReferenceFields(
		Item\Block $block,
		Item\FieldCollection $registeredFields,
		Item\Member $member,
	): Item\FieldCollection
	{
		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = new CRM\FieldCode($fieldCode);
		$fieldDescription = $fieldCode->getDescription($member->presetId);
		if (empty($fieldDescription))
		{
			return new Item\FieldCollection();
		}

		$fieldType = match ($fieldDescription['TYPE'] ?? '')
		{
			FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
			FieldType::LIST => FieldType::LIST,
			FieldType::DOUBLE => FieldType::DOUBLE,
			FieldType::INTEGER => FieldType::INTEGER,
			FieldType::ADDRESS => FieldType::ADDRESS,
			default => FieldType::STRING,
		};
		$itemsDescription = $fieldDescription['ITEMS'] ?? null;
		$items = null;
		if ($itemsDescription !== null)
		{
			$items = new Item\Field\ItemCollection();
			foreach ($itemsDescription as $itemDescription)
			{
				$items->add(
					new Item\Field\Item(
						id: $itemDescription['ID'], value: $itemDescription['VALUE'],
					),
				);
			}
		}

		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode->getCode());

		$field = $registeredFields->getFirstFieldByName($fieldName);
		if ($field !== null)
		{
			return new Item\FieldCollection($field);
		}

		$field = new Item\Field(
			0,
			$member->party,
			$fieldType,
			$fieldName,
			$fieldDescription['CAPTION'] ?? null,
			connectorType: ConnectorType::CRM_ENTITY,
			// todo: dont use \CCrmOwnerType
			entityType: (
				$fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartDocumentName
				|| $fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartB2eDocumentName
			)
				? EntityType::DOCUMENT
				: EntityType::MEMBER
			,
			entityCode: $fieldCode->getEntityFieldCode(),
			items: $items,
		);

		if ($field->type === FieldType::ADDRESS)
		{
			$field->subfields = $this->createAddressSubfieldsByField($field);
		}

		return new Item\FieldCollection($field);
	}

	private function createRequisiteFields(
		Item\Block $block,
		Item\Member $member,
	): Item\FieldCollection
	{
		$memberConnector = $this->memberConnectorFactory->createRequisiteConnector($member);
		if ($memberConnector === null)
		{
			return new Item\FieldCollection();
		}

		$result = new Item\FieldCollection();

		$requisiteFields = $memberConnector->fetchRequisite(
			new Item\Connector\FetchRequisiteModifier($member->presetId),
		);
		foreach ($requisiteFields as $requisiteField)
		{
			$fieldCode = new CRM\FieldCode($requisiteField->name);
			$fieldDescription = $fieldCode->getDescription() ?? [];

			$fieldType = match ($fieldDescription['TYPE'] ?? null)
			{
				FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
				FieldType::LIST => FieldType::LIST,
				FieldType::DOUBLE => FieldType::DOUBLE,
				FieldType::INTEGER => FieldType::INTEGER,
				FieldType::ADDRESS => FieldType::ADDRESS,
				default => FieldType::STRING,
			};
			$itemsDescription = $fieldDescription['ITEMS'] ?? null;
			$items = null;
			if ($itemsDescription !== null)
			{
				$items = new Item\Field\ItemCollection();
				foreach ($itemsDescription as $itemDescription)
				{
					$items->add(
						new Item\Field\Item(
							id: $itemDescription['ID'], value: $itemDescription['VALUE'],
						),
					);
				}
			}

			$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $requisiteField->name);
			$fieldEntityType = $fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartDocumentName
				? EntityType::DOCUMENT
				: EntityType::MEMBER
			;
			$field = new Item\Field(
				0,
				$member->party,
				$fieldType,
				$fieldName,
				$requisiteField->label,
				ConnectorType::REQUISITE,
				// todo: dont use \CCrmOwnerType
				$fieldEntityType,
				items: $items,
			);

			if ($field->type === FieldType::ADDRESS)
			{
				$field->subfields = $this->createAddressSubfieldsByField($field);
			}

			$result->add($field);
		}

		return $result;
	}

	private function createAddressSubfieldsByField(Item\Field $field): Item\FieldCollection
	{
		$result = new Item\FieldCollection();
		$parsedParentFieldName = NameHelper::parse($field->name);

		foreach (self::ADDRESS_SUBFIELD_CODES as $addressSubfieldCode)
		{
			$field = new Item\Field(
				0, $field->party, FieldType::STRING, // all address subfields has string type
				NameHelper::create(
					$parsedParentFieldName['blockCode'],
					$parsedParentFieldName['fieldType'],
					$parsedParentFieldName['party'],
					$parsedParentFieldName['fieldCode'],
					$addressSubfieldCode,
				), $this->getAddressFieldLabels()[$addressSubfieldCode] ?? null,
			);

			$field->required = in_array($addressSubfieldCode, self::REQUIRED_ADDRESS_SUBFIELDS, true);

			$result->add($field);
		}

		return $result;
	}

	private function getAddressFieldLabels(): array
	{
		// todo: encapsulate it to another class
		if (!Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return \Bitrix\Crm\EntityAddress::getLabels();
	}

	private function createB2eReferenceFields(
		Item\Block $block,
		Item\Member $member,
		Item\Document $document,
		Item\FieldCollection $registeredFields,
	): Item\FieldCollection
	{
		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		$profileProvider = Container::instance()->getServiceProfileProvider();
		if (!$profileProvider->isProfileField($fieldCode))
		{
			return $this->createCrmReferenceFields($block, $registeredFields, $member);
		}

		if ($block->role === Type\Member\Role::ASSIGNEE && $document->representativeId === null)
		{
			return new Item\FieldCollection();
		}

		$fieldDescription = $profileProvider->getDescriptionByFieldName($fieldCode);
		$fieldType = $this->convertUserFieldType($fieldDescription['type'] ?? '');

		if ($fieldCode === self::SNILS_FIELD_CODE)
		{
			$fieldType = FieldType::SNILS;
		}

		$fieldCode = self::USER_FIELD_CODE_PREFIX . $fieldCode;
		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode);

		return $this->makeFieldsByUserFieldDescription(
			$fieldName,
			$fieldType,
			$fieldDescription,
			$member->party,
			$registeredFields,
		);
	}

	public function createByRequired(
		Item\Document $document,
		Item\MemberCollection $members,
		Item\B2e\RequiredField $requiredField,
	): ?Item\Field
	{
		$firstByRole = $members->findFirstByRole($requiredField->role);
		if (!$firstByRole)
		{
			return null;
		}

		$block = $this->blockFactory->makeStubBlockByRequiredField($document, $requiredField, $firstByRole->party);
		if (!$block)
		{
			return null;
		}

		return $this->createByBlocks(new Item\BlockCollection($block), $firstByRole, $document)->getFirst();
	}

	public function createDocumentMemberFields(
		Item\Document $document,
		Item\Member $member,
		bool $withValues = false,
	): Item\FieldCollection
	{
		$blocks = $this->blockRepository
			->getCollectionByBlankId($document->blankId)
			->filterByRole($member->role)
		;

		$fieldNameKeyMap = [];
		foreach ($blocks as $block)
		{
			$fields = $this->createByBlocks(new Item\BlockCollection($block), $member, $document);
			foreach ($fields as $field)
			{
				$fieldNameKeyMap[$field->name] = $field;
				if ($withValues)
				{
					$this->createAndAppendValueToFieldWithSubfields($block, $field, $member, $document);
				}
			}
		}

		foreach ($this->getB2eRequiredFields($document) as $requiredField)
		{
			if ($member->role !== $requiredField->role)
			{
				continue;
			}

			$block = $this->blockFactory->makeStubBlockByRequiredField($document, $requiredField, $member->party);
			$fields = $this->createByBlocks(new Item\BlockCollection($block), $member, $document);
			foreach ($fields as $field)
			{
				if (isset($fieldNameKeyMap[$field->name]))
				{
					continue;
				}
				$fieldNameKeyMap[$field->name] = $field;
				if ($withValues)
				{
					$this->createAndAppendValueToFieldWithSubfields($block, $field, $member, $document);
				}
			}
		}

		return new Item\FieldCollection(...array_values($fieldNameKeyMap));
	}

	private function createAndAppendValueToFieldWithSubfields(
		Item\Block $block,
		Item\Field $field,
		Item\Member $member,
		Item\Document $document,
	): void
	{
		if (!$field->subfields)
		{
			$field->replaceValueIfPresent($this->fieldValueFactory->createByBlock($block, $field, $member, $document));

			return;
		}

		foreach ($field->subfields as $subfield)
		{
			$subfield->replaceValueIfPresent($this->fieldValueFactory->createByBlock($block, $subfield, $member, $document));
		}
	}

	private function getB2eRequiredFields(Item\Document $document): Item\B2e\RequiredFieldsCollection
	{
		if (!Type\DocumentScenario::isB2EScenario($document->scenario) || !$document->id || !$document->companyUid)
		{
			return new Item\B2e\RequiredFieldsCollection();
		}

		$operation = new GetRequiredFieldsWithCache(
			documentId: $document->id,
			companyUid: $document->companyUid,
		);
		$result = $operation->launch();

		return $result instanceof B2eRequiredFieldsResult ? $result->collection : new Item\B2e\RequiredFieldsCollection();
	}

	public function createDocumentFutureSignerFields(
		Item\Document $document,
		int $userId,
		bool $withValues = true,
	): Item\FieldCollection
	{
		$member = new Item\Member(
			documentId: $document->id,
			party: 1,
			entityType: \Bitrix\Sign\Type\Member\EntityType::USER,
			entityId: $userId,
			role: Type\Member\Role::SIGNER,
		);

		$fields = $this->createDocumentMemberFields($document, $member, $withValues);

		// don't show trusted fields
		return $fields->filter(static fn(Item\Field $field) => !$field->values?->getFirst()?->trusted);
	}

	private function createDynamicMemberFields(
		Item\Block $block,
		Item\Member $member,
		Item\Document $document,
		Item\FieldCollection $registeredFields,
	): Item\FieldCollection
	{
		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		$fieldDescription = (new MemberDynamicFieldInfoProvider())->getFieldDescription($fieldCode);
		if (empty($fieldDescription))
		{
			return new Item\FieldCollection();
		}

		$fieldType = $this->convertUserFieldType($fieldDescription['type'] ?? '');
		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode);

		return $this->makeFieldsByUserFieldDescription(
			$fieldName,
			$fieldType,
			$fieldDescription,
			$member->party,
			$registeredFields,
		);
	}

	private function convertUserFieldType(string $userFieldType): string
	{
		return match ($userFieldType)
		{
			FieldType::SNILS => FieldType::SNILS,
			FieldType::FIRST_NAME => FieldType::FIRST_NAME,
			FieldType::LAST_NAME => FieldType::LAST_NAME,
			FieldType::PATRONYMIC => FieldType::PATRONYMIC,
			FieldType::POSITION => FieldType::POSITION,
			FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
			FieldType::LIST, FieldType::ENUMERATION => FieldType::LIST,
			FieldType::DOUBLE => FieldType::DOUBLE,
			FieldType::INTEGER => FieldType::INTEGER,
			FieldType::ADDRESS => FieldType::ADDRESS,
			default => FieldType::STRING
		};
	}

	/**
	 * @param array{type: string, items: array} $fieldDescription
	 *
	 * @return Item\Field\ItemCollection|null
	 */
	private function convertUserFieldItems(array $fieldDescription): ?Item\Field\ItemCollection
	{
		if (
			empty($fieldDescription['items'])
			|| !is_array($fieldDescription['items'])
			&& FieldType::LIST !== $this->convertUserFieldType($fieldDescription['type'] ?? '')
		)
		{
			return null;
		}

		$itemCollection = new Item\Field\ItemCollection();
		foreach ($fieldDescription['items'] as $item)
		{
			$itemCollection->add(
				new Item\Field\Item(
					id: $item['id'], value: $item['value'],
				),
			);
		}

		return $itemCollection;
	}

	private function makeFieldsByUserFieldDescription(
		string $fieldName,
		string $fieldType,
		array $fieldDescription,
		int $party,
		Item\FieldCollection $registeredFields,
	): Item\FieldCollection
	{
		$field = $registeredFields->getFirstFieldByName($fieldName);
		if ($field !== null)
		{
			return new Item\FieldCollection($field);
		}

		$field = new Item\Field(
			blankId: 0,
			party: $party,
			type: $fieldType,
			name: $fieldName,
			label: $fieldDescription['caption'] ?? '',
			items: $this->convertUserFieldItems($fieldDescription),
			required: $this->getFieldRequiredByType($fieldType),
		);

		if ($field->type === FieldType::ADDRESS)
		{
			$field->subfields = $this->createAddressSubfieldsByField($field);
		}

		return new Item\FieldCollection($field);
	}

	private function createHcmLinkFields(
		Item\Block $block,
		Item\Member $member,
		Item\Document $document,
		Item\FieldCollection $registeredFields,
	): Item\FieldCollection
	{
		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		if (!$this->hcmLinkFieldService->isAvailable())
		{
			return new Item\FieldCollection();
		}

		$parsedName = $this->hcmLinkFieldService->parseName($fieldCode);
		if (!$parsedName || $parsedName->integrationId !== $document->hcmLinkCompanyId)
		{
			return new Item\FieldCollection();
		}

		$fieldType = $this->hcmLinkFieldService->getFieldTypeByName($fieldCode);
		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode);

		$field = $registeredFields->getFirstFieldByName($fieldName);
		if ($field !== null)
		{
			return new Item\FieldCollection($field);
		}

		$hcmField = $this->hcmLinkFieldService->getFieldById($parsedName->id);

		$field = new Item\Field(
			blankId: 0,
			party: $member->party,
			type: $fieldType,
			name: $fieldName,
			label: $hcmField->title ?? '',
			required: $this->getFieldRequiredByType($fieldType),
		);

		if ($field->type === FieldType::ADDRESS)
		{
			$field->subfields = $this->createAddressSubfieldsByField($field);
		}

		return new Item\FieldCollection($field);
	}

	private function getFieldRequiredByType(string $fieldType): ?bool
	{
		return in_array($fieldType, self::NOT_REQUIRED_FIELD_TYPES, true) ? false : null;
	}
}

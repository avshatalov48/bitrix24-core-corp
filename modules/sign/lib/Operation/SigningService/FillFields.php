<?php

namespace Bitrix\Sign\Operation\SigningService;

use Bitrix\HumanResources\Result\Service\HcmLink\GetFieldValueResult;
use Bitrix\Main;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Factory;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation\GetRequiredFieldsWithCache;
use Bitrix\Sign\Operation\Result\FillFieldsResult;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Repository\FieldValueRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Operation\SigningService\HcmLinkFieldLoadResult;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkFieldRequestResult;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkJobsCheckResult;
use Bitrix\Sign\Service\Api\Document\FieldService;
use Bitrix\Sign\Service\Cache\Memory\Sign\UserCache;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkFieldService;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkService;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Service\Result\Sign\Block\B2eRequiredFieldsResult;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type;
use Bitrix\Main\Localization\Loc;

final class FillFields implements Contract\Operation
{
	private Factory\Field $fieldFactory;
	private Factory\Api\Property\Request\Field\Fill\Value $fieldFillRequestValueFactory;
	private Factory\FieldValue $fieldValueFactory;
	private MemberRepository $memberRepository;
	private BlockRepository $blockRepository;
	private FieldService $apiDocumentFieldService;
	private ProfileProvider $profileProvider;
	private readonly HcmLinkFieldService $hcmLinkFieldService;
	private UserCache $userCache;
	private readonly int $limit;
	private readonly FieldValueRepository $fieldValueRepository;
	private ?Item\Field\FieldValueCollection $fieldValues = null;
	private Item\Field\HcmLink\HcmLinkFieldDelayedValueReferenceMap $hcmLinkValueReferenceMap;
	private Item\B2e\RequiredFieldsCollection $requiredFieldsCollection;
	private readonly \Bitrix\Sign\Blanks\Block\Factory $blockFactory;
	private readonly LegalInfoProvider $legalInfoProvider;
	private readonly MemberService $memberService;
	private readonly HcmLinkService $hcmLinkService;

	public function __construct(
		private readonly Item\Document $document,
	)
	{
		$this->fieldFactory = new Factory\Field();
		$this->fieldFillRequestValueFactory = new Factory\Api\Property\Request\Field\Fill\Value();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->blockRepository = Container::instance()->getBlockRepository();
		$this->apiDocumentFieldService = Container::instance()->getApiDocumentFieldService();
		$this->profileProvider = Container::instance()->getServiceProfileProvider();
		$this->userCache = new UserCache();
		$this->memberRepository->setUserCache($this->userCache);
		$this->profileProvider->setCache($this->userCache);
		$this->limit = Storage::instance()->getFieldsFillMembersLimit();
		$this->fieldValueFactory = new Factory\FieldValue($this->profileProvider);
		$this->fieldValueRepository = Container::instance()->getFieldValueRepository();
		$this->hcmLinkFieldService = Container::instance()->getHcmLinkFieldService();
		$this->blockFactory = new \Bitrix\Sign\Blanks\Block\Factory();
		$this->legalInfoProvider = Container::instance()->getLegalInfoProvider();
		$this->memberService = Container::instance()->getMemberService();
		$this->hcmLinkService = Container::instance()->getHcmLinkService();
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
			return new FillFieldsResult(true);
		}
		$this->loadSignFields($members);

		$blocks = $this->blockRepository->getCollectionByBlankId($this->document->blankId);
		if (
			Type\DocumentScenario::isB2EScenario($this->document->scenario)
			&& $this->document->scheme === Type\Document\SchemeType::ORDER
		)
		{
			$blocks = $blocks->filterExcludeRole(Type\Member\Role::SIGNER);
		}

		$blocks = $blocks->filterExcludeParty(0);

		$membersFields = new Item\Api\Property\Request\Field\Fill\MemberFieldsCollection();
		$this->hcmLinkValueReferenceMap = new Item\Field\HcmLink\HcmLinkFieldDelayedValueReferenceMap();

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
			$this->appendB2eRequiredFields($member, $requestFields);

			if (!$requestFields->isEmpty())
			{
				$memberFields = new Item\Api\Property\Request\Field\Fill\MemberFields($member->uid, $requestFields);
				$this->checkAndSetTrusted($memberFields);
				$membersFields->addItem($memberFields);
			}
		}

		if (empty($membersFields->toArray()))
		{
			$this->memberRepository->markAsConfigured($members);

			return $this->makeResult($members->count());
		}

		$result = $this->bunchLoadHcmFieldValues($members, $membersFields, $blocks);
		if (!$result instanceof HcmLinkFieldLoadResult)
		{
			return $result;
		}

		if ($result->shouldWait)
		{
			return new FillFieldsResult(false);
		}

		$response = $this->apiDocumentFieldService->fill(
			new Item\Api\Document\Field\FillRequest(
				$this->document->uid,
				$membersFields,
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

		if ($fieldValue instanceof Item\Field\HcmLink\HcmLinkDelayedValue)
		{
			$this->hcmLinkValueReferenceMap->add($fieldValue);
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
		return $this->getFieldValueFromLocalFields($member, $field)
			?? $this->fieldValueFactory->createByBlock($block, $field, $member, $this->document)
		;
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

	private function getRequiredFields(): Item\B2e\RequiredFieldsCollection
	{
		if (!isset($this->requiredFieldsCollection))
		{
			$operation = new GetRequiredFieldsWithCache(
				documentId: $this->document->id,
				companyUid: $this->document->companyUid,
			);
			$result = $operation->launch();

			$this->requiredFieldsCollection = $result instanceof B2eRequiredFieldsResult
				? $result->collection :
				new Item\B2e\RequiredFieldsCollection()
			;
		}

		return $this->requiredFieldsCollection;
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

	private function loadSignFields(Item\MemberCollection $members): void
	{
		if ($this->canDocumentContainLocalFieldValues())
		{
			$this->fieldValues = $this->fieldValueRepository->listByMemberIds($members->getIds());
		}
	}

	private function canDocumentContainLocalFieldValues(): bool
	{
		return Type\DocumentScenario::isB2EScenario($this->document->scenario)
			&& $this->document->initiatedByType === Type\Document\InitiatedByType::EMPLOYEE;
	}

	private function getFieldValueFromLocalFields(
		Item\Member $member,
		Item\Field $field,
	): ?Item\Field\Value
	{
		if ($this->fieldValues === null)
		{
			return null;
		}

		foreach ($this->fieldValues as $fieldValue)
		{
			if (
				$fieldValue instanceof Item\Field\FieldValue
				&& $fieldValue->memberId === $member->id
				&& $fieldValue->fieldName === $field->name
			)
			{
				return new Item\Field\Value(0, text: $fieldValue->value);
			}
		}

		return null;
	}

	private function bunchLoadHcmFieldValues(
		Item\MemberCollection $members,
		Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $membersFields,
		Item\BlockCollection $blocks,
	): Main\Result|HcmLinkFieldLoadResult
	{
		if (!$this->document->hcmLinkCompanyId)
		{
			return new HcmLinkFieldLoadResult(false);
		}

		$fieldIds = $this->hcmLinkValueReferenceMap->getFieldIds();
		$employeeIds = $this->hcmLinkValueReferenceMap->getEmployeeIds();
		if (empty($fieldIds) || empty($employeeIds))
		{
			return new HcmLinkFieldLoadResult(false);
		}

		$result = $this->requestFieldsIfNeed($members, $employeeIds, $fieldIds);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->hcmLinkService->isAllJobsDone($this->getMemberJobIds($members));
		if (!$result instanceof HcmLinkJobsCheckResult)
		{
			return (new Main\Result())
				->addError(new Main\Error(Loc::getMessage('SIGN_OPERATION_SIGNING_SERVICE_FILL_FIELDS_HCM_JOB_ERROR')))
			;
		}

		if (!$result->isDone)
		{
			return new HcmLinkFieldLoadResult(true);
		}

		$result = $this->fillDelayedValuesByReference($employeeIds, $fieldIds);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->updateLegalInfo($membersFields, $members, $blocks);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new HcmLinkFieldLoadResult(false);
	}

	private function requestFieldsIfNeed(
		Item\MemberCollection $members,
		array $employeeIds,
		array $fieldIds,
	): Main\Result
	{
		$notRequested = $members->filter(
			fn(Item\Member $member) => !$member->hcmLinkJobId && in_array($member->employeeId, $employeeIds, true),
		);

		if ($notRequested->isEmpty())
		{
			return new Main\Result();
		}

		$notRequestedEmployeeIds = [];
		foreach ($notRequested as $member)
		{
			$notRequestedEmployeeIds[] = $member->employeeId;
		}
		$result = $this->hcmLinkFieldService
			->requestFieldValues($this->document->hcmLinkCompanyId, $notRequestedEmployeeIds, $fieldIds)
		;

		if (!$result instanceof HcmLinkFieldRequestResult)
		{
			return $result;
		}

		$jobId = $result->jobId;

		foreach ($notRequested as $member)
		{
			$member->hcmLinkJobId = $jobId;
			$updateResult = $this->memberRepository->update($member);
			if (!$updateResult->isSuccess())
			{
				return $updateResult;
			}
		}

		return new Main\Result();
	}

	private function getMemberJobIds(Item\MemberCollection $members): array
	{
		$ids = [];
		foreach ($members as $member)
		{
			if ($member->hcmLinkJobId)
			{
				$ids[$member->hcmLinkJobId] = $member->hcmLinkJobId;
			}
		}

		return array_values($ids);
	}

	private function fillDelayedValuesByReference(array $employeeIds, array $fieldIds): Main\Result
	{
		$result = $this->hcmLinkFieldService->getFieldValue($employeeIds, $fieldIds);
		if (!$result instanceof GetFieldValueResult)
		{
			return $result;
		}

		foreach ($result->collection as $valueItem)
		{
			foreach ($this->hcmLinkValueReferenceMap->get($valueItem->employeeId, $valueItem->fieldId) as $requestValue)
			{
				$requestValue->value = $valueItem->value;
			}
		}

		return new Main\Result();
	}

	private function appendB2eRequiredFields(
		Item\Member $member,
		Item\Api\Property\Request\Field\Fill\FieldCollection $requestFields,
	): void
	{
		if (!Type\DocumentScenario::isB2EScenario($this->document->scenario))
		{
			return;
		}

		foreach ($this->getRequiredFields() as $requiredField)
		{
			if ($requiredField->role !== $member->role)
			{
				continue;
			}

			$block = $this->blockFactory->makeStubBlockByRequiredField($this->document, $requiredField, $member->party);
			if (!$block)
			{
				continue;
			}

			$fields = $this->fieldFactory->createByBlocks(new Item\BlockCollection($block), $member, $this->document);
			foreach ($fields as $field)
			{
				if (!in_array($field->name, $requestFields->getNames(), true))
				{
					$this->addFieldValueToRequest($block, $field, $member, $requestFields);
				}
			}
		}
	}

	private function updateLegalInfo(
		Item\Api\Property\Request\Field\Fill\MemberFieldsCollection $membersFields,
		Item\MemberCollection $members,
		Item\BlockCollection $blocks,
	): Main\Result
	{
		$membersByUidMap = $this->getMemberByUidMap($members);

		foreach ($membersFields->items as $memberFields)
		{
			$member = $membersByUidMap[$memberFields->memberId];
			if (!$member instanceof Item\Member)
			{
				continue;
			}

			foreach ($memberFields->fields->toArray() as $field)
			{
				if ($field->trusted && $this->isHcmLinkField($field->name))
				{
					$result = $this->updateLegalInfoByField($field, $member);
					if (!$result->isSuccess())
					{
						return $result;
					}
				}
			}

			$this->updateMemberRequestProfileFields($member, $blocks, $memberFields->fields);
			$this->checkAndSetTrusted($memberFields);
		}

		return new Main\Result();
	}

	private function isHcmLinkField(string $fieldCode): bool
	{
		return str_starts_with($fieldCode, $this->hcmLinkFieldService::FIELD_PREFIX);
	}

	private function updateLegalInfoByField(
		Item\Api\Property\Request\Field\Fill\Field $requestField,
		Item\Member $member,
	): Main\Result
	{

		$item = $requestField->value->getFirst();
		if (!$item instanceof Item\Field\HcmLink\HcmLinkDelayedValue || $item->value === '')
		{
			return new Main\Result();
		}

		['fieldCode' => $fieldCode] = NameHelper::parse($requestField->name);
		$parsedName = $this->hcmLinkFieldService->parseName($fieldCode);
		if ($parsedName === null)
		{
			return new Main\Result();
		}

		$legalType = $this->hcmLinkFieldService->convertHcmLinkIntTypeToLegalInfoType($parsedName->type);
		if (!$legalType)
		{
			return new Main\Result();
		}

		$field = $this->legalInfoProvider->getLegalInfoFieldByType($legalType);
		if (!$field)
		{
			return new Main\Result();
		}

		$userId = $this->memberService->getUserIdForMember($member, $this->document);
		if ($userId === null)
		{
			return new Main\Result();
		}

		return $this->profileProvider->updateFieldData($userId, $field->name, $item->value);
	}

	/**
	 * @param Item\MemberCollection $members
	 *
	 * @return array<string, Item\Member>
	 */
	private function getMemberByUidMap(Item\MemberCollection $members): array
	{
		$map = [];
		foreach ($members as $member)
		{
			$map[$member->uid] = $member;
		}

		return $map;
	}

	private function updateMemberRequestProfileFields(
		Item\Member $member,
		Item\BlockCollection $blocks,
		Item\Api\Property\Request\Field\Fill\FieldCollection $requestFields,
	): void
	{
		$roleBlocks = $blocks->filterByRole($member->role);
		foreach ($roleBlocks as $block)
		{
			$fields = $this->fieldFactory->createByBlocks(new Item\BlockCollection($block), $member, $this->document);
			foreach ($fields as $field)
			{
				['fieldCode' => $fieldCode, 'fieldType' => $fieldType] = NameHelper::parse($field->name);
				if (
					$this->profileProvider->isFieldCodeUserProfileField($fieldCode)
					&& $this->hcmLinkFieldService->getSpecialHcmFieldTypeBySignFieldType($fieldType)
				)
				{
					$this->replaceValueInRequestField($block, $field, $member, $requestFields);
				}
			}
		}
	}

	private function replaceValueInRequestField(
		Item\Block $block,
		Item\Field $field,
		Item\Member $member,
		Item\Api\Property\Request\Field\Fill\FieldCollection $requestFields
	): void
	{
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

		foreach ($requestFields->toArray() as $requestField)
		{
			if ($requestField->name === $field->name)
			{
				$requestField->value = new Item\Api\Property\Request\Field\Fill\FieldValuesCollection($fieldValue);
				$requestField->trusted = $value->trusted ?? false;

				break;
			}
		}
	}

}

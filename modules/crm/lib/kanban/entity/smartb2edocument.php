<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity\Dto\Sign\B2e\UserListDto;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\ResultStatusField;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\UserListField;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\UserNameListField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Service\Container as SignContainer;
use Bitrix\Sign\Service\Integration\Crm\Kanban\B2e\EntityService;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\MemberStatus;

final class SmartB2eDocument extends Dynamic
{
	private readonly ?EntityService $entityService;
	private ?DocumentCollection $documentCollection = null;
	private ?MemberCollection $readyForActionReviewOrEditMemberCollection = null;
	private ?MemberCollection $notCompletedReviewOrEditMemberCollection = null;
	private ?MemberCollection $reviewerAndEditorMemberCollection = null;
	private ?MemberCollection $currentUserReadyForSignMemberCollection = null;

	public function __construct()
	{
		parent::__construct();
		$this->entityService = Loader::includeModule('sign') && method_exists(
			SignContainer::instance(), 'getCrmKanbanB2eEntityService',
		) ? SignContainer::instance()->getCrmKanbanB2eEntityService() : null;
	}

	private function getCustomFields(): array
	{
		return [
			Item\SmartB2eDocument::FIELD_NAME_CREATED_TIME => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_CREATED_TIME'),
				'field' => null,
			],
			Item\SmartB2eDocument::FIELD_NAME_ASSIGNEE_MEMBER => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_ASSIGNEE_MEMBER'),
				'field' => Service\Display\Field::createByType(
					Field::TYPE_USER,
					Item\SmartB2eDocument::FIELD_NAME_ASSIGNEE_MEMBER,
				)->addDisplayParams([
					'AS_ARRAY' => true,
				]),
			],
			Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_SIGN_CANCELLED_MEMBER'),
				'field' => Service\Display\Field::createByType(
					Field::TYPE_USER,
					Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER,
				)->addDisplayParams([
					'AS_ARRAY' => true,
				]),
			],
			Item\SmartB2eDocument::FIELD_NAME_REVIEWER_MEMBER_LIST => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_REVIEWER_MEMBER_LIST'),
				'field' => Service\Display\Field::createByType(
					Field::TYPE_USER,
					Item\SmartB2eDocument::FIELD_NAME_REVIEWER_MEMBER_LIST,
				)->addDisplayParams([
					'AS_ARRAY' => true,
				]),
			],
			Item\SmartB2eDocument::FIELD_NAME_EDITOR_MEMBER_LIST => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_EDITOR_MEMBER_LIST'),
				'field' => Service\Display\Field::createByType(
					Field::TYPE_USER,
					Item\SmartB2eDocument::FIELD_NAME_EDITOR_MEMBER_LIST,
				)->addDisplayParams([
					'AS_ARRAY' => true,
				]),
			],
			Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_EMPLOYER_LIST => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_NOT_SIGNED_EMPLOYER_LIST'),
				'field' => Service\Display\Field::createByType(
					UserNameListField::TYPE,
					Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_EMPLOYER_LIST,
				)->setVisibleCount(1)
					->setFilterMemberStatus([MemberStatus::READY]),
			],
			Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_COMPANY_LIST => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_NOT_SIGNED_COMPANY_LIST'),
				'field' => Service\Display\Field::createByType(
					UserNameListField::TYPE,
					Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_COMPANY_LIST,
				)->setVisibleCount(1)
					->setFilterMemberStatus([MemberStatus::WAIT]),
			],
			Item\SmartB2eDocument::FIELD_NAME_EMPLOYER_LIST => [
				'title' => Loc::getMessage(
					'CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_EMPLOYER_LIST',
				),
				'field' => Service\Display\Field::createByType(
					UserNameListField::TYPE,
					Item\SmartB2eDocument::FIELD_NAME_EMPLOYER_LIST,
				)->setVisibleCount(1)
					->setFilterMemberStatus([MemberStatus::WAIT]),
			],
			Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER_LIST => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_SIGN_CANCELLED_MEMBER_LIST'),
				'field' => Service\Display\Field::createByType(
					UserListField::TYPE,
					Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER_LIST,
				)->setVisibleCount(4)
					->setFilterMemberStatus([
						MemberStatus::READY,
						MemberStatus::WAIT,
						MemberStatus::STOPPED,
						MemberStatus::REFUSED,
					]),
			],
			Item\SmartB2eDocument::FIELD_NAME_SIGN_RESULT_STATUS => [
				'title' => Loc::getMessage('CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_SIGN_RESULT_STATUS'),
				'field' => Service\Display\Field::createByType(
					ResultStatusField::TYPE,
					Item\SmartB2eDocument::FIELD_NAME_SIGN_RESULT_STATUS,
				),
			],
		];
	}

	public function getFilterPresets(): array
	{
		return (new \Bitrix\Crm\Filter\Preset\SmartB2EDocument())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->setStagesEnabled($this->factory->isStagesEnabled())
			->setCategoryId($this->categoryId)
			->getDefaultPresets()
		;
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = parent::getDefaultAdditionalSelectFields();
		$fields[Item\SmartB2eDocument::FIELD_NAME_TITLE] = '';
		foreach ($this->getCustomFields() as $name => $customField)
		{
			$fields[$name] = (string)($customField['title'] ?? null);
		}

		return $fields;
	}

	protected function getExtraDisplayedFields(): array
	{
		$fields = parent::getExtraDisplayedFields();
		if ($this->entityService === null)
		{
			return $fields;
		}

		foreach ($this->getCustomFields() as $name => $customField)
		{
			$field = $customField['field'] ?? null;
			if(is_object($field) === false)
			{
				continue;
			}

			$fields[$name] = $field;
		}

		return $fields;
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions, string $semantics = PhaseSemantics::UNDEFINED): bool
	{
		return false;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function getItems(array $parameters): \CDBResult
	{
		$result = parent::getItems($parameters);
		//@codingStandardsIgnoreStart
		if (is_array($result->arResult))
		{
			$this->documentCollection = $this->getDocumentCollectionFromArResult($result->arResult);
			$this->reviewerAndEditorMemberCollection = $this->getReviewerAndEditorMemberList($this->documentCollection);
			$this->notCompletedReviewOrEditMemberCollection = $this->reviewerAndEditorMemberCollection?->filter(
				static fn(Member $member): bool => in_array(
					$member->status,
					[MemberStatus::READY, MemberStatus::WAIT],
					true,
				),
			);
			$this->readyForActionReviewOrEditMemberCollection = $this->notCompletedReviewOrEditMemberCollection?->filter(
				static fn(Member $member): bool => $member->status === MemberStatus::READY,
			);
			$this->currentUserReadyForSignMemberCollection = $this->getReadyForSignMemberList($this->documentCollection);
		}
		//@codingStandardsIgnoreEnd

		return $result;
	}

	private function getReviewerAndEditorMemberList(?DocumentCollection $documentCollection): ?MemberCollection
	{
		if ($this->entityService === null)
		{
			return null;
		}

		$documentIds = $this->getDocumentIds($documentCollection);

		if ($documentIds === null)
		{
			return null;
		}

		return $this->entityService->getReviewerOrEditorMemberList($documentIds);
	}

	private function getReadyForSignMemberList(?DocumentCollection $documentCollection): ?MemberCollection
	{
		if ($this->entityService === null)
		{
			return null;
		}

		$documentIds = $this->getDocumentIds($documentCollection);

		if ($documentIds === null)
		{
			return null;
		}

		return $this->entityService->getCurrentUserReadyForSignListByDocumentId($documentIds);
	}

	private function getDocumentIds(?DocumentCollection $documentCollection): ?array
	{
		if ($documentCollection === null)
		{
			return null;
		}

		$documentIds = [];
		foreach ($documentCollection as $document)
		{
			if ($document?->status === DocumentStatus::SIGNING)
			{
				$documentIds[] = $document?->id;
			}
		}

		return $documentIds;
	}

	//@codingStandardsIgnoreStart
	private function getDocumentCollectionFromArResult(array $arResult): ?DocumentCollection
	{
		if ($this->entityService === null)
		{
			return null;
		}

		$itemIds = array_map(fn(array $item): int => (int)($item['ID'] ?? 0), $arResult);
		$itemIds = array_filter($itemIds, fn(int $itemId): bool => $itemId > 0);

		return $this->entityService->getDocumentListByEntityIds($itemIds);
	}
	//@codingStandardsIgnoreEnd

	public function prepareItemCommonFields(array $item): array
	{
		$entityId = (int)($item['ID'] ?? null);
		$customData = $this->getB2eCustomItemsData($entityId);
		if (!empty($customData))
		{
			$item = array_merge($item, $customData);
		}

		return parent::prepareItemCommonFields($item);
	}

	private function getB2eCustomItemsData(int $entityId): array
	{
		$itemData = [];

		if ($entityId < 1 || $this->entityService === null)
		{
			return $itemData;
		}

		$document = $this->documentCollection?->getByEntityId($entityId);
		if ($document === null)
		{
			return $itemData;
		}

		$isShowAssignee = false;
		$isShowCancelled = false;
		if ($document->status === DocumentStatus::SIGNING)
		{
			if ($this->isReviewOrEditCompleted((int)$document->id))
			{
				$isShowAssignee = true;
				$itemData[Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_EMPLOYER_LIST] = $this->getSigningReadyUserList(
					$document,
				);
				$itemData[Item\SmartB2eDocument::FIELD_NAME_NOT_SIGNED_COMPANY_LIST] = $this->getSigningWaitUserList(
					$document,
				);
			}
			else
			{
				$itemData[Item\SmartB2eDocument::FIELD_NAME_EMPLOYER_LIST] = $this->getSigningWaitUserList(
					$document,
				);
				$reviewerUserId = $this->getReviewerUserId($document);

				$itemData[Item\SmartB2eDocument::FIELD_NAME_REVIEWER_MEMBER_LIST] = $reviewerUserId;
				$itemData[Item\SmartB2eDocument::FIELD_NAME_EDITOR_MEMBER_LIST] = $reviewerUserId ? null : $this->getEditorUserId(
					$document,
				);
			}
		}
		elseif (in_array($document->status, [DocumentStatus::DONE, DocumentStatus::STOPPED], true))
		{
			$isShowAssignee = $document->status === DocumentStatus::DONE;
			$isShowCancelled = $document->status === DocumentStatus::STOPPED;
			if($document->status === DocumentStatus::DONE)
			{
				$itemData[Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER_LIST] = $this->getStoppedUserList(
					$document,
				);
			}
			$itemData[Item\SmartB2eDocument::FIELD_NAME_SIGN_RESULT_STATUS] = $document->status;
		}

		if ($document->representativeId !== null)
		{
			if ($isShowAssignee === true)
			{
				$itemData[Item\SmartB2eDocument::FIELD_NAME_ASSIGNEE_MEMBER] = $document->representativeId;
			}
			elseif($isShowCancelled === true)
			{
				$itemData[Item\SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER] = $document->stoppedById;
			}
		}

		return $itemData;
	}

	private function isReviewOrEditCompleted(int $documentId): bool
	{
		$result = $this->notCompletedReviewOrEditMemberCollection?->filter(
			fn(Member $member): bool => $member->documentId === $documentId,
		);

		return $result && $result->count() === 0;
	}

	private function getStoppedUserList(Document $document): UserListDto
	{
		$documentId = (int)$document->id;
		$memberCollection = $this->entityService?->getStoppedUserListByDocumentId(
			$documentId,
		);

		return $this->getUserListDto($memberCollection);
	}

	private function getUserListDto(?MemberCollection $memberCollection): UserListDto
	{
		$userIdList = [];
		$total = 0;

		if ($memberCollection)
		{
			foreach ($memberCollection as $member)
			{
				$entityId = $member?->entityId;
				if (is_int($entityId))
				{
					$userIdList[] = $entityId;
				}
			}

			$total = $memberCollection->getQueryTotal() ?: $memberCollection->count();
		}

		return new UserListDto([
			'total' => $total,
			'userIdList' => $userIdList,
		]);
	}

	private function getSigningReadyUserList(Document $document): UserListDto
	{
		$documentId = (int)$document->id;
		$memberCollection = $this->entityService?->getSigningReadyUserListByDocumentId(
			$documentId,
		);

		return $this->getUserListDto($memberCollection);
	}

	private function getSigningWaitUserList(Document $document): UserListDto
	{
		$documentId = (int)$document->id;
		$memberCollection = $this->entityService?->getSigningWaitUserListByDocumentId(
			$documentId,
		);

		return $this->getUserListDto($memberCollection);
	}

	private function getReviewerUserList(Document $document): UserListDto
	{
		$documentId = (int)$document->id;
		$memberCollection = $this->entityService?->getReviewerMemberList(
			$documentId,
			$this->reviewerAndEditorMemberCollection,
		);

		$readyMember = $memberCollection->findFirst(
			fn(Member $member): bool => $member->status === MemberStatus::READY,
		);

		if ($readyMember === null)
		{
			return $this->getUserListDto(null);
		}

		$memberCollection->sort(static fn(Member $member): int => ($member->status === MemberStatus::READY) ? -1 : 1);

		return $this->getUserListDto($memberCollection);
	}

	private function getReviewerUserId(Document $document): ?int
	{
		$documentId = (int)$document->id;
		$reviewMemberCollection = $this->entityService?->getReviewerMemberList(
			$documentId,
			$this->notCompletedReviewOrEditMemberCollection,
		);

		return $reviewMemberCollection?->getFirst()?->entityId;
	}

	private function getEditorUserId(Document $document): ?int
	{
		$documentId = (int)$document->id;

		$memberCollection = $this->entityService?->getEditorMemberList(
			$documentId,
			$this->notCompletedReviewOrEditMemberCollection,
		);

		return $memberCollection?->getFirst()?->entityId;
	}

	private function getCustomFieldsForConfig(): array
	{
		$customFields = [];
		foreach ($this->getCustomFields() as $name => $customField)
		{
			$customFields[] = [
				'name' => $name,
				'title' => $customField['title'] ?? '',
			];
		}

		return $customFields;
	}

	private function getCustomFieldsForPopup(): array
	{
		$customFields = [];
		foreach ($this->getCustomFields() as $name => $customField)
		{
			$customFields[$name] = [
				'ID' => 'field_' . $name,
				'NAME' => $name,
				'LABEL' => (string)($customField['title'] ?? null),
			];
		}

		return $customFields;
	}

	public function prepareFieldsSections(array $configuration): array
	{
		if ($this->entityService)
		{
			$configuration = array_map(
				function (array $configurationSection): array
				{
					$elements = $configurationSection['elements'] ?? null;
					if (is_array($elements))
					{
						$configurationSection['elements'] = array_merge(
							$elements,
							$this->getCustomFieldsForConfig(),
						);
					}

					return $configurationSection;
				},
				$configuration,
			);
		}

		return parent::prepareFieldsSections($configuration);
	}

	protected function getPopupGeneralFields(): array
	{
		$result = parent::getPopupGeneralFields();

		if ($this->entityService)
		{
			$result = array_merge(
				$result,
				$this->getCustomFieldsForPopup(),
			);
		}

		return $result;
	}

	public function appendAdditionalData(array &$rows): void
	{
		if ($this->entityService === null)
		{
			return;
		}

		foreach ($rows as $key => $row)
		{
			$entityId = (int)($row['id'] ?? null);
			if ($entityId)
			{
				$document = $this->documentCollection?->getByEntityId($entityId);
				$reviewerItem = null;
				$editorItem = null;
				$signItem = null;
				$memberId = null;
				$isDocumentCompleted = in_array($document?->status, [DocumentStatus::DONE, DocumentStatus::STOPPED]);

				if ($document?->status === DocumentStatus::SIGNING)
				{
					$reviewerItem = $this->entityService->findCurrentUserReviewerItemByDocumentId(
						$document?->id,
						$this->readyForActionReviewOrEditMemberCollection,
					);
					$memberId = $reviewerItem?->id;
					if ($memberId === null)
					{
						$editorItem = $this->entityService->findCurrentUserEditorItemByDocumentId(
							$document?->id,
							$this->readyForActionReviewOrEditMemberCollection,
						);
						$memberId = $editorItem?->id;
					}

					if ($memberId === null)
					{
						$signItem = $this?->currentUserReadyForSignMemberCollection->findFirst(
							fn(Member $member): bool => $member->documentId === $document?->id,
						);

						$memberId = $signItem?->id;
					}
				}

				$rows[$key]['isUserCanCancel'] = $document
					&& $this->entityService->isCurrentUserCanCancelDocument($document);
				$rows[$key]['isUserCanSign'] = $document && $signItem;
				$rows[$key]['isUserCanReview'] = $document && $reviewerItem;
				$rows[$key]['isUserCanEdit'] = $document && $editorItem;
				$rows[$key]['memberId'] = $memberId;
				$rows[$key]['entityId'] = $document?->entityId;
				$rows[$key]['documentUid'] = $document?->uid;

				$isDraft = $document?->status === DocumentStatus::UPLOADED;
				$rows[$key]['isUserCanPreview'] = !$isDocumentCompleted;
				$rows[$key]['isUserCanModify'] = $isDraft;
				$rows[$key]['isUserCanProcess'] = $isDocumentCompleted;
			}
		}
	}
}

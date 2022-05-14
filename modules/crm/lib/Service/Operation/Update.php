<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Item;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Timeline\MarkController;
use Bitrix\Crm\Timeline\RelationController;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class Update extends Operation
{
	public function __construct(Item $item, Operation\Settings $settings, Collection $fieldsCollection = null)
	{
		parent::__construct($item, $settings, $fieldsCollection);
		$this->bizProcEventType = \CCrmBizProcEventType::Edit;
	}

	public function checkAccess(): Result
	{
		$result = new Result();

		if(!Container::getInstance()
			->getUserPermissions(
				$this->getContext()->getUserId()
			)
			->canUpdateItem(
				$this->item
			)
		)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_UPDATE_DENIED'),
					static::ERROR_CODE_ITEM_UPDATE_ACCESS_DENIED
				)
			);
		}

		return $result;
	}

	protected function save(): Result
	{
		return $this->item->save();
	}

	public function isItemChanged(): bool
	{
		foreach ($this->fieldsCollection as $field)
		{
			if ($field->isValueCanBeChanged() && $this->item->isChanged($field->getName()))
			{
				return true;
			}
		}

		$additionalFields = [Item::FIELD_NAME_PRODUCTS, Item::FIELD_NAME_FM];

		foreach ($additionalFields as $fieldName)
		{
			if (
				$this->item->hasField($fieldName)
				&& $this->item->isChanged($fieldName)
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function isCheckRequiredOnlyChanged(): bool
	{
		if ($this->item->isCategoriesSupported() && $this->item->isChangedCategoryId())
		{
			return true;
		}
		if ($this->item->isStagesEnabled() && $this->item->isChangedStageId())
		{
			return false;
		}

		return true;
	}

	protected function updateDuplicates(): void
	{
		parent::updateDuplicates();

		if ($this->isDuplicatesIndexInvalidationEnabled())
		{
			$itemBeforeSave = $this->getItemBeforeSave();
			$item = $this->getItem();

			Integrity\DuplicateManager::markDuplicateIndexAsDirty($item->getEntityTypeId(), $item->getId());

			if (
				$item->hasField(Item::FIELD_NAME_ASSIGNED)
				&& $item->getAssignedById() !== $itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED)
			)
			{
				Integrity\DuplicateManager::onChangeEntityAssignedBy($item->getEntityTypeId(), $item->getId());
			}
		}
	}

	protected function registerDuplicateCriteria(): void
	{
		$registrar = Integrity\DuplicateManager::getCriterionRegistrar($this->getItem()->getEntityTypeId());

		$registrar->updateByItem($this->getItemBeforeSave(), $this->getItem());
	}

	protected function isCountersUpdateNeeded(): bool
	{
		$difference = ComparerBase::compareEntityFields(
			$this->itemBeforeSave->getData(Values::ACTUAL),
			$this->item->getData(),
		);

		return (
			$difference->isChanged(Item::FIELD_NAME_ASSIGNED)
			|| $difference->isChanged(Item::FIELD_NAME_STAGE_ID)
			|| $difference->isChanged(Item::FIELD_NAME_CATEGORY_ID)
		);
	}

	protected function getUserIdsForCountersReset(): array
	{
		if (!$this->item->hasField(Item::FIELD_NAME_ASSIGNED))
		{
			return [];
		}

		$userIds = [];

		$assigned = $this->item->getAssignedById();
		if ($assigned > 0)
		{
			$userIds[] = $assigned;
		}

		$previousAssigned = $this->itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED);
		if ($previousAssigned > 0 && $assigned !== $previousAssigned)
		{
			$userIds[] = $previousAssigned;
		}

		return $userIds;
	}

	protected function getCountersCodes(): array
	{
		$codes = parent::getCountersCodes();

		if (!$this->item->isCategoriesSupported())
		{
			return $codes;
		}

		$previousCategoryId = $this->itemBeforeSave->remindActual(Item::FIELD_NAME_CATEGORY_ID);
		$currentCategoryId = $this->item->getCategoryId();

		if ($previousCategoryId !== $currentCategoryId)
		{
			$codesForPreviousCategory = EntityCounterManager::prepareCodes(
				$this->item->getEntityTypeId(),
				$this->getTypesOfCountersToReset(),
				[
					'CATEGORY_ID' => $previousCategoryId,
				],
			);

			$codes = array_merge($codes, $codesForPreviousCategory);
		}

		return $codes;
	}

	protected function autocompleteActivities(): Result
	{
		if ($this->wasItemMovedToFinalStage())
		{
			$authorId = $this->getContext()->getUserId();
			if ($authorId <= 0)
			{
				$authorId = $this->getItem()->getUpdatedBy();
			}

			\CCrmActivity::SetAutoCompletedByOwner(
				$this->getItem()->getEntityTypeId(),
				$this->getItem()->getId(),
				$this->getActivityProvidersToAutocomplete(),
				['CURRENT_USER' => $authorId]
			);
		}

		return new Result();
	}

	private function wasItemMovedToFinalStage(): bool
	{
		if (!$this->getItem()->hasField(Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		$previousStageId = (string)$this->getItemBeforeSave()->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = (string)$this->getItem()->getStageId();

		return ComparerBase::isMovedToFinalStage($this->getItem()->getEntityTypeId(), $previousStageId, $currentStageId);
	}

	protected function registerStatistics(Statistics\OperationFacade $statisticsFacade): Result
	{
		return $statisticsFacade->update($this->itemBeforeSave, $this->item);
	}

	protected function saveToHistory(): Result
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		$trackedObject = $factory->getTrackedObject($this->itemBeforeSave, $this->item);

		return Container::getInstance()->getEventHistory()->registerUpdate($trackedObject);
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController([
			'ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()
		]);

		if ($timelineController)
		{
			$timelineController->onModify(
				$this->itemBeforeSave->getId(),
				[
					'PREVIOUS_FIELDS' => $this->itemBeforeSave->getData(Values::ACTUAL),
					'CURRENT_FIELDS' => $this->item->getData(),
					'FIELDS_MAP' => $this->item->getFieldsMap(),
				]
			);
		}

		RelationController::getInstance()->registerEventsByFieldsChange(
			$this->getItemIdentifier(),
			$this->fieldsCollection->toArray(),
			$this->itemBeforeSave->getData(Values::ACTUAL),
			$this->item->getData(),
			$this->getItemsThatExcludedFromTimelineRelationEventsRegistration()
		);

		$factory = Container::getInstance()->getFactory($this->getItem()->getEntityTypeId());

		if ($factory->isClientEnabled())
		{
			RelationController::getInstance()->registerEventsByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Contact,
				$this->itemBeforeSave->remindActual(Item::FIELD_NAME_CONTACT_BINDINGS),
				$this->item->getContactBindings(),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration()
			);
		}

		if ($factory->isStagesEnabled() && $this->wasItemMovedToFinalStage())
		{
			MarkController::getInstance()->onItemMoveToFinalStage(
				$this->getItemIdentifier(),
				$factory->getStageSemantics((string)$this->item->getStageId()),
			);
		}
	}

	protected function sendPullEvent(): void
	{
		parent::sendPullEvent();

		\Bitrix\Crm\Kanban\SupervisorTable::sendItem(
			$this->item->getId(),
			\CCrmOwnerType::ResolveName($this->item->getEntityTypeId()),
			'kanban_update'
		);

		PullManager::getInstance()->sendItemUpdatedEvent($this->pullItem, $this->pullParams);
	}

	protected function runAutomation(): Result
	{
		$result = parent::runAutomation();

		if($result->isSuccess())
		{
			/** @var \Bitrix\Crm\Automation\Starter $starter */
			$starter = $result->getData()['starter'];

			return $starter->runOnUpdate(
				Helper::prepareCompatibleData(
					$this->itemBeforeSave->getEntityTypeId(),
					$this->itemBeforeSave->getCompatibleData(Values::CURRENT)
				),
				Helper::prepareCompatibleData(
					$this->itemBeforeSave->getEntityTypeId(),
					$this->itemBeforeSave->getCompatibleData(Values::ACTUAL)
				)
			);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @return Result
	 */
	public function checkRunningWorkflows(): Result
	{
		$result = new Result();

		if (
			$this->item->isCategoriesSupported()
			&& $this->item->isChangedCategoryId()
			&& $this->bizProcHelper::HasRunningWorkflows($this->item->getEntityTypeId(), $this->item->getId())
		)
		{
			$result->addError(new Error(
				Loc::getMessage('CRM_OPERATION_UPDATE_ITEM_HAS_RUNNING_WORKFLOWS'),
				static::ERROR_CODE_ITEM_HAS_RUNNING_WORKFLOWS
			));
		}

		return $result;
	}

	protected function checkLimits(): Result
	{
		$result = parent::checkLimits();

		$restriction = RestrictionManager::getDynamicTypesLimitRestriction();
		if ($restriction->isUpdateItemRestricted($this->item->getEntityTypeId()))
		{
			$result->addError($restriction->getUpdateItemRestrictedError());
		}
		$updateOperationRestriction = RestrictionManager::getUpdateOperationRestriction($this->getItemIdentifier());
		if (!$updateOperationRestriction->hasPermission())
		{
			$result->addError(
				new Error(
					$updateOperationRestriction->getErrorMessage(),
					$updateOperationRestriction->getErrorCode()
				)
			);
		}

		return $result;
	}

	protected function isClearItemCategoryCacheNeeded(): bool
	{
		return $this->item->isCategoriesSupported() && $this->item->isChangedCategoryId();
	}
}

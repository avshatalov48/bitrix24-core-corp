<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Timeline\MarkController;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;
use CCrmActivity;

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
		return $this->item->save(
			$this->isCheckFieldsEnabled()
			&& $this->isCheckRequiredUserFields()
			&& $this->isCheckRequiredByAttributeUserFields()
		);
	}

	public function isItemChanged(): bool
	{
		foreach ($this->fieldsCollection as $field)
		{
			if ($this->item->isFieldDisabled($field->getName()))
			{
				continue;
			}

			if ($field->isValueCanBeChanged() && $this->item->isChanged($field->getName()))
			{
				return true;
			}
		}

		$additionalFields = [Item::FIELD_NAME_PRODUCTS, Item::FIELD_NAME_FM, Item::FIELD_NAME_LAST_ACTIVITY_TIME];

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

	/**
	 * @inheritDoc
	 */
	protected function isCheckRequiredByAttributeUserFields(): bool
	{
		return !$this->wasItemMovedToFailStage();
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

	protected function notifyCounterMonitor(): void
	{
		$oldFieldsValues = [];
		$newFieldsValues = [];
		foreach ($this->getCounterMonitorSignificantFields() as $commonFieldName => $entityFieldName)
		{
			$oldFieldsValues[$entityFieldName] = $this->itemBeforeSave->remindActual($commonFieldName);
			$newFieldsValues[$entityFieldName] = $this->item->get($commonFieldName);
		}

		\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityUpdate(
			$this->getItem()->getEntityTypeId(),
			$oldFieldsValues,
			$newFieldsValues
		);
	}

	protected function autocompleteActivities(): Result
	{
		if ($this->wasItemMovedToFinalStage() && !empty($this->getActivityProvidersToAutocomplete()))
		{
			$authorId = $this->getContext()->getUserId();
			if ($authorId <= 0)
			{
				$authorId = $this->getItem()->getUpdatedBy();
			}

			CCrmActivity::SetAutoCompletedByOwner(
				$this->getItem()->getEntityTypeId(),
				$this->getItem()->getId(),
				$this->getActivityProvidersToAutocomplete(),
				[
					'CURRENT_USER' => $authorId,
				]
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

		if ($this->getItemBeforeSave())
		{
			$previousStageId = (string)$this->getItemBeforeSave()->remindActual(Item::FIELD_NAME_STAGE_ID);
		}
		else
		{
			$previousStageId = (string)$this->getItem()->remindActual(Item::FIELD_NAME_STAGE_ID);
		}

		$currentStageId = (string)$this->getItem()->getStageId();

		return ComparerBase::isMovedToFinalStage($this->getItem()->getEntityTypeId(), $previousStageId, $currentStageId);
	}

	private function wasItemMovedToFailStage(): bool
	{
		if (!$this->getItem()->hasField(Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		if ($this->getItemBeforeSave())
		{
			$previousStageId = (string)$this->getItemBeforeSave()->remindActual(Item::FIELD_NAME_STAGE_ID);
		}
		else
		{
			$previousStageId = (string)$this->getItem()->remindActual(Item::FIELD_NAME_STAGE_ID);
		}

		$currentStageId = (string)$this->getItem()->getStageId();

		return ComparerBase::isMovedToFailStage($this->getItem()->getEntityTypeId(), $previousStageId, $currentStageId);
	}

	protected function registerStatistics(Statistics\OperationFacade $statisticsFacade): Result
	{
		return $statisticsFacade->update($this->itemBeforeSave, $this->item);
	}

	protected function saveToHistory(): Result
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		$trackedObject = $factory->getTrackedObject($this->itemBeforeSave, $this->item);

		$updateResult = Container::getInstance()->getEventHistory()->registerUpdate($trackedObject, $this->getContext());

		$registrar = Container::getInstance()->getRelationRegistrar();

		$registrar->registerByFieldsChange(
			$this->getItemIdentifier(),
			$this->fieldsCollection->toArray(),
			$this->itemBeforeSave->getData(Values::ACTUAL),
			$this->item->getData(),
			$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
			$this->getContext(),
		);

		if ($this->item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			$registrar->registerByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Contact,
				$this->itemBeforeSave->remindActual(Item::FIELD_NAME_CONTACT_BINDINGS),
				$this->item->getContactBindings(),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
				$this->getContext(),
			);
		}

		if ($this->item->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS))
		{
			$registrar->registerByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Company,
				$this->itemBeforeSave->remindActual(Item\Contact::FIELD_NAME_COMPANY_BINDINGS),
				$this->item->get(Item\Contact::FIELD_NAME_COMPANY_BINDINGS),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
				$this->getContext(),
			);
		}

		return $updateResult;
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

		if ($this->isFinalizedWithStages())
		{
			MarkController::getInstance()->onItemMoveToFinalStage(
				$this->getItemIdentifier(),
				(string)$this->item->getStageId(),
				$this->getContext()->getUserId(),
			);
		}
	}

	protected function sendPullEvent(): void
	{
		parent::sendPullEvent();

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

		$updateOperationRestriction = RestrictionManager::getUpdateOperationRestriction($this->getItemIdentifier());
		if (!$updateOperationRestriction->hasPermission())
		{
			$result->addError(
				new Error(
					$updateOperationRestriction->getErrorMessage(),
					$updateOperationRestriction->getErrorCode(),
				)
			);
		}

		return $result;
	}

	protected function isClearItemCategoryCacheNeeded(): bool
	{
		return $this->item->isCategoriesSupported() && $this->item->isChangedCategoryId();
	}

	protected function isClearItemStageCacheNeeded(): bool
	{
		return $this->item->isStagesEnabled() && $this->item->isChangedStageId();
	}

	public function isFinalizedWithStages(): bool
	{
		$factory = Container::getInstance()->getFactory($this->getItem()->getEntityTypeId());

		return $factory?->isStagesEnabled() && $this->wasItemMovedToFinalStage();
	}

	private function isTransitionAllowed(): bool
	{
		if ($this->item->getStageId() === $this->item->remindActual('STAGE_ID'))
		{
			return true;
		}

		if ($this->item->isCategoriesSupported() && ($this->item->getCategoryId() !== $this->item->remindActual('CATEGORY_ID')))
		{
			return true;
		}

		return Container::getInstance()->getUserPermissions($this->getContext()->getUserId())->isStageTransitionAllowed(
			$this->item->remindActual('STAGE_ID'),
			$this->item->getStageId(),
			ItemIdentifier::createByItem($this->item),
		);
	}

	protected function preSaveChecks(): ?Result
	{
		$checkResult = parent::preSaveChecks();
		if ($checkResult)
		{
			return $checkResult;
		}

		if (!$this->isCheckAccessEnabled())
		{
			return null;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());

		if ($userPermissions->isAdminForEntity($this->item->getEntityTypeId()))
		{
			return null;
		}

		if ($this->item->isStagesEnabled() && !$this->isTransitionAllowed())
		{
			return (new Result())->addError(new Error(Loc::getMessage('CRM_PERMISSION_STAGE_TRANSITION_NOT_ALLOWED')));
		}

		return null;
	}
}

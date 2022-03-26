<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
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

	protected function isCountersUpdateNeeded(): bool
	{
		return true;
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
					'EXTENDED_MODE' => true,
					'DEAL_CATEGORY_ID' => $previousCategoryId,
				],
			);

			$codes = array_merge($codes, $codesForPreviousCategory);
		}

		return $codes;
	}

	protected function getUserIdsForCountersReset(): array
	{
		$userIds = [];

		$previousAssigned = $this->itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED);
		$currentAssigned = $this->item->getAssignedById();

		if ($previousAssigned !== $currentAssigned)
		{
			if ($previousAssigned > 0)
			{
				$userIds[] = $previousAssigned;
			}

			if ($currentAssigned > 0)
			{
				$userIds[] = $currentAssigned;
			}
		}

		return $userIds;
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
				]
			);
		}

		RelationController::getInstance()->registerEventsByFieldsChange(
			$this->getItemIdentifier(),
			$this->fieldsCollection->toArray(),
			$this->itemBeforeSave->getCompatibleData(Values::ACTUAL),
			$this->item->getCompatibleData(),
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

		if (!$factory->isStagesSupported())
		{
			return;
		}

		$newStage = $factory->getStage((string)$this->item->getStageId());
		if (!$newStage)
		{
			return;
		}

		$wasItemMovedToFinalStage = (
			$factory->isStagesEnabled()
			&& ($this->itemBeforeSave->remindActual(Item::FIELD_NAME_STAGE_ID) !== $this->item->getStageId())
			&& PhaseSemantics::isFinal($newStage->getSemantics())
		);

		if ($wasItemMovedToFinalStage)
		{
			MarkController::getInstance()->onItemMoveToFinalStage(
				$this->getItemIdentifier(),
				$newStage->getSemantics()
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
}

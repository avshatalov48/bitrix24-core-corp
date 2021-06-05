<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class Update extends Operation
{
	/** @var \CCrmBizProcHelper */
	protected $bizProcHelper = \CCrmBizProcHelper::class;

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

		return (
			$this->item->hasField(Item::FIELD_NAME_PRODUCTS)
			&& $this->item->isChanged(Item::FIELD_NAME_PRODUCTS)
		);
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

	protected function saveToHistory(): Result
	{
		$trackedObject = Container::getInstance()->getFactory($this->item->getEntityTypeId())->getTrackedObject($this->itemBeforeSave, $this->item);

		return Container::getInstance()->getEventHistory()->registerUpdate($trackedObject);
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController(['ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()]);
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

	public function runAutomation(): Result
	{
		$result = parent::runAutomation();

		if($result->isSuccess())
		{
			/** @var \Bitrix\Crm\Automation\Starter $starter */
			$starter = $result->getData()['starter'];
			return $starter->runOnUpdate($this->itemBeforeSave->getData(Values::CURRENT), []);
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

		return $result;
	}
}

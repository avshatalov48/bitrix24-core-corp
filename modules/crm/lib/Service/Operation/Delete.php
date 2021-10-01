<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Timeline\FactoryBasedController;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Delete extends Operation
{
	public function checkAccess(): Result
	{
		$result = new Result();

		if (!Container::getInstance()->getUserPermissions(
				$this->getContext()->getUserId()
			)->canDeleteItem($this->item)
		)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_DELETE_DENIED'),
					static::ERROR_CODE_ITEM_DELETE_ACCESS_DENIED
				)
			);
		}

		return $result;
	}

	protected function save(): Result
	{
		return $this->item->delete();
	}

	protected function saveToHistory(): Result
	{
		//todo remove access to the factory
		$trackedObject = Container::getInstance()
			->getFactory($this->itemBeforeSave->getEntityTypeId())
			->getTrackedObject($this->itemBeforeSave);

		return Container::getInstance()->getEventHistory()->registerDelete($trackedObject);
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController(['ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()]);
		if ($timelineController)
		{
			/** @see FactoryBasedController::onDelete() */
			$timelineController->onDelete($this->itemBeforeSave->getId(), ['FIELDS' => $this->itemBeforeSave->getData()]);
		}
	}

	/**
	 * There is no need to process field values during deleting.
	 *
	 * @return Result
	 */
	public function processFieldsBeforeSave(): Result
	{
		return new Result();
	}

	/**
	 * There is no need to check field values during deleting.
	 *
	 * @return Result
	 */
	public function checkFields(): Result
	{
		return new Result();
	}

	public function processFieldsAfterSave(): Result
	{
		return new Result();
	}

	protected function updateSearchIndexes(): void
	{
		\CCrmSearch::DeleteSearch(\CCrmOwnerType::ResolveName($this->item->getEntityTypeId()), $this->item->getId());
	}

	protected function sendPullEvent(): void
	{
		PullManager::getInstance()->sendItemDeletedEvent($this->pullItem, $this->pullParams);
	}

	protected function getPullData(): array
	{
		return $this->getItemBeforeSave()->getCompatibleData();
	}
}

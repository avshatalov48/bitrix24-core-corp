<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Cleaning;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Delete extends Operation
{
	/** @var Cleaning\Cleaner */
	protected $cleaner;

	public function setCleaner(Cleaning\Cleaner $cleaner): self
	{
		$this->cleaner = $cleaner;

		return $this;
	}

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
		$result = $this->item->delete();

		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($this->isDeferredCleaningEnabled())
		{
			Cleaning\CleaningManager::register($this->itemBeforeSave->getEntityTypeId(), $this->itemBeforeSave->getId());
		}
		else
		{
			$cleaningResult = $this->runCleaning();

			if (!$cleaningResult->isSuccess())
			{
				$result->addErrors($cleaningResult->getErrors());
			}
		}

		return $result;
	}

	protected function notifyCounterMonitor(): void
	{
		$fieldsValues = [];
		foreach ($this->getCounterMonitorSignificantFields() as $commonFieldName => $entityFieldName)
		{
			$fieldsValues[$entityFieldName] = $this->itemBeforeSave->remindActual($commonFieldName);
		}
		\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityDelete($this->getItem()->getEntityTypeId(), $fieldsValues);
	}

	protected function registerStatistics(Statistics\OperationFacade $statisticsFacade): Result
	{
		return $statisticsFacade->delete($this->itemBeforeSave);
	}

	protected function saveToHistory(): Result
	{
		$trackedObject = Container::getInstance()
			->getFactory($this->itemBeforeSave->getEntityTypeId())
			->getTrackedObject($this->itemBeforeSave);

		return Container::getInstance()->getEventHistory()->registerDelete($trackedObject, $this->getContext());
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController([
			'ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()
		]);
		if ($timelineController)
		{
			$timelineController->onDelete(
				$this->itemBeforeSave->getId(),
				[
					'FIELDS' => $this->itemBeforeSave->getData(),
					'FIELDS_MAP' => $this->itemBeforeSave->getFieldsMap(),
				]);
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

	protected function runAutomation(): Result
	{
		$entityTypeId = $this->itemBeforeSave->getEntityTypeId();
		$entityId = $this->itemBeforeSave->getId();
		$documentId = $this->bizProcHelper::ResolveDocumentId($entityTypeId, $entityId);

		$deleteErrors = [];
		\CBPDocument::OnDocumentDelete($documentId, $deleteErrors);
		\Bitrix\Crm\Automation\QR\QrTable::deleteByEntity($entityTypeId, $entityId);

		$result = new Result();
		foreach ($deleteErrors as $error)
		{
			$result->addError(
				new Error(
					$error['message'] ?? '',
					$error['code'] ?? 0,
					$error['file'] ?? ''
				)
			);
		}

		return $result;
	}

	public function processFieldsAfterSave(): Result
	{
		return new Result();
	}

	protected function updateSearchIndexes(): void
	{
		$itemBeforeSave = $this->getItemBeforeSave();

		\CCrmSearch::DeleteSearch(\CCrmOwnerType::ResolveName($itemBeforeSave->getEntityTypeId()), $itemBeforeSave->getId());

		\Bitrix\Crm\Search\SearchContentBuilderFactory::create($itemBeforeSave->getEntityTypeId())
			->removeShortIndex($itemBeforeSave->getId())
		;
	}

	protected function updateDuplicates(): void
	{
		parent::updateDuplicates();

		$itemBeforeSave = $this->getItemBeforeSave();

		if ($this->isDuplicatesIndexInvalidationEnabled())
		{
			Integrity\DuplicateManager::markDuplicateIndexAsJunk($itemBeforeSave->getEntityTypeId(), $itemBeforeSave->getId());
		}

		Integrity\DuplicateIndexMismatch::unregisterEntity($itemBeforeSave->getEntityTypeId(), $itemBeforeSave->getId());
	}

	protected function registerDuplicateCriteria(): void
	{
		$registrar = Integrity\DuplicateManager::getCriterionRegistrar($this->getItemBeforeSave()->getEntityTypeId());

		$registrar->unregisterByItem($this->getItemBeforeSave());
	}

	protected function sendPullEvent(): void
	{
		PullManager::getInstance()->sendItemDeletedEvent($this->pullItem, $this->pullParams);
	}

	protected function getPullData(): array
	{
		return $this->getItemBeforeSave()->getCompatibleData();
	}

	protected function keepCurrentUser(): bool
	{
		return true;
	}

	protected function updatePermissions(): void
	{
		$item = $this->getItemBeforeSave();
		$permissionEntityType = \Bitrix\Crm\Service\UserPermissions::getItemPermissionEntityType($item);

		\Bitrix\Crm\Security\Manager::resolveController($permissionEntityType)
			->unregister(
				$permissionEntityType,
				$item->getId()
			)
		;
	}

	protected function runCleaning(): Result
	{
		if (!$this->cleaner)
		{
			$result = new Result();

			$result->addError(
				new Error('Instance of ' . Cleaning\Cleaner::class . ' is not found in ' . static::class),
			);

			return $result;
		}

		return $this->cleaner->cleanup();
	}

	protected function isClearItemCategoryCacheNeeded(): bool
	{
		return true;
	}
}

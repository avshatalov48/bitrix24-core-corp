<?php

namespace Bitrix\Crm\Reservation\Component;

use Bitrix\Crm\Item;
use Bitrix\Crm\Reservation\Error\InventoryManagementError;
use Bitrix\Crm\Activity\Provider\StoreDocument;
use Bitrix\Crm\Reservation\Error\AvailabilityServices;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Main\Result;
use Bitrix\Crm\Service\Operation\Action\CreateFinalSummaryTimelineHistoryItem;

class DealUpdateAction
{
	private int $id;
	private bool $isFactoryEnabled;
	private ?Item $itemBeforeSave = null;
	private ?Factory $factory = null;
	private ?Result $inventoryManagementCheckResult = null;

	public function __construct(int $id)
	{
		$this->id = $id;

		$this->isFactoryEnabled = DealSettings::getCurrent()->isFactoryEnabled();
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
	}

	public function before(array &$fields, callable $onError = null): void
	{
		$this->itemBeforeSave = null;
		$this->inventoryManagementCheckResult = null;

		if (
			$this->isFactoryEnabled
			|| !$this->factory
			|| $this->id <= 0
		)
		{
			return;
		}

		$this->itemBeforeSave = $this->factory->getItem($this->id);
		if (!$this->itemBeforeSave)
		{
			return;
		}

		$inventoryManagementChecker = new InventoryManagementChecker($this->itemBeforeSave);
		$this->inventoryManagementCheckResult = $inventoryManagementChecker->checkBeforeUpdate($fields);
		if (!$this->inventoryManagementCheckResult->isSuccess())
		{
			if (
				$this->inventoryManagementCheckResult->getErrorCollection()->getErrorByCode(
					InventoryManagementError::INVENTORY_MANAGEMENT_ERROR_CODE
				)
			)
			{
				StoreDocument::addProductActivity($this->id);
			}

			if (
				$this->inventoryManagementCheckResult->getErrorCollection()->getErrorByCode(
					AvailabilityServices::AVAILABILITY_SERVICES_ERROR_CODE
				)
			)
			{
				StoreDocument::addServiceActivity($this->id);
			}

			if (is_callable($onError))
			{
				call_user_func(
					$onError,
					$this->inventoryManagementCheckResult
				);
			}
		}

		$fields = $this->inventoryManagementCheckResult->getData();
	}

	public function after(callable $onError = null): void
	{
		if (
			$this->isFactoryEnabled
			|| !$this->factory
			|| $this->id <= 0
			|| !isset($this->itemBeforeSave)
		)
		{
			return;
		}

		$itemAfterSave = $this->factory->getItem($this->id);
		if (!$itemAfterSave)
		{
			return;
		}

		if (
			\CCrmSaleHelper::isProcessInventoryManagement()
			&& isset($this->inventoryManagementCheckResult)
			&& $this->inventoryManagementCheckResult->isSuccess()
		)
		{
			$processInventoryManagementResult =
				(new InventoryManagement($this->itemBeforeSave, $itemAfterSave))
					->process()
			;
			if (!$processInventoryManagementResult->isSuccess())
			{
				if (is_callable($onError))
				{
					call_user_func(
						$onError,
						$processInventoryManagementResult
					);
				}
			}
		}

		$itemAfterSave = $this->factory->getItem($this->id);
		if ($itemAfterSave)
		{
			(new CreateFinalSummaryTimelineHistoryItem())
				->setItemBeforeSave($this->itemBeforeSave)
				->process($itemAfterSave)
			;
		}
	}
}

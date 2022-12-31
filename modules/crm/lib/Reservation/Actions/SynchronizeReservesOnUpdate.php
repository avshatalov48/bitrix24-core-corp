<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Synchronize reserves on update deal.
 *
 * It is processed in two stages: before and after saving, and you need to use the same instance.
 * Example:
 * ```php
	public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
	{
		$operation = parent::getUpdateOperation($item, $context);

		$synchronizeReserveOperation = new SynchronizeReservesOnUpdate();

		$operation
			->addAction(
				Operation::ACTION_BEFORE_SAVE,
				$synchronizeReserveOperation
			)
			->addAction(
				Operation::ACTION_AFTER_SAVE,
				$synchronizeReserveOperation
			)
		;

		// ...
	}
 * ```
 */
class SynchronizeReservesOnUpdate extends SynchronizeReserves
{
	private bool $isFirstRun = true;

	/**
	 * @inheritDoc
	 */
	public function process(Item $item): Result
	{
		$result = new Result();

		$isAfterOperation = $this->getItemBeforeSave() !== null;
		if ($this->isFirstRun && $isAfterOperation)
		{
			$result->addError(
				new Error('Action can only be executing on both steps at once (before and after saving)')
			);
			return $result;
		}

		// runs and before, and after for processing new product rows (by before step the rows not contains ID)
		$productRows = $item->getProductRows();
		if ($productRows instanceof ProductRowCollection)
		{
			$this->fillReservationResult($productRows);
		}

		if ($this->isFirstRun)
		{
			$this->isFirstRun = false;
		}
		else
		{
			$this->synchronizeReserves($item->getId());
		}

		return $result;
	}
}

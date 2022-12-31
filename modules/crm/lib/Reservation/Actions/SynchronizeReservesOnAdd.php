<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Synchronize reserves on create deal.
 *
 * Used only after saving.
 */
class SynchronizeReservesOnAdd extends SynchronizeReserves
{
	/**
	 * @inheritDoc
	 */
	public function process(Item $item): Result
	{
		$result = new Result();

		$isAfterOperation = $this->getItemBeforeSave() !== null;
		if (!$isAfterOperation)
		{
			$result->addError(
				new Error('Action can only be executing after saving')
			);
			return $result;
		}

		$productRows = $item->getProductRows();
		if ($productRows instanceof ProductRowCollection)
		{
			$this->fillReservationResult($productRows);
			$this->synchronizeReserves($item->getId());
		}

		return $result;
	}
}

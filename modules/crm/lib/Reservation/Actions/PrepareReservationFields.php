<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class PrepareReservationFields extends Base
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$isAfterOperation = $this->getItemBeforeSave() !== null;
		if ($isAfterOperation)
		{
			$result->addError(
				new Error('Action can only be executing before saving')
			);

			return $result;
		}

		$productRows = $item->getProductRows();
		if ($productRows instanceof ProductRowCollection)
		{
			$this->prepareReservationFields($productRows);
		}

		return $result;
	}

	protected function prepareReservationFields(ProductRowCollection $productRows): void
	{
		$defaultDateReserveEnd = ReservationService::getInstance()->getDefaultDateReserveEnd();

		foreach ($productRows as $row)
		{
			$productReservation = $row->getProductRowReservation();
			if (!$productReservation)
			{
				continue;
			}

			$oldValues = $productReservation->collectValues(Values::ACTUAL);
			$newValues = $productReservation->collectValues(Values::CURRENT);

			$oldDateReserveEnd = $oldValues['DATE_RESERVE_END'] ?? null;
			if (empty($newValues))
			{
				if (!$oldDateReserveEnd)
				{
					$productReservation->setDateReserveEnd($defaultDateReserveEnd);
				}
			}
			else
			{
				if (array_key_exists('DATE_RESERVE_END', $newValues))
				{
					$newDateReserveEnd = (string)$newValues['DATE_RESERVE_END'];
					if ($newDateReserveEnd === '')
					{
						$productReservation->setDateReserveEnd($defaultDateReserveEnd);
					}
				}
			}
		}
	}
}

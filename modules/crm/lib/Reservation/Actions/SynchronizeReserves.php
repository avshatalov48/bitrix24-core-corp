<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Crm\Reservation\ProductRowReservation;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use CCrmOwnerType;

/**
 * Base action of synchronize `crm` and `sale` reserves.
 */
abstract class SynchronizeReserves extends Action
{
	private ReservationResult $reservationResult;
	private array $processedRowsIds = [];

	/**
	 * Fill reserve info by processed product rows.
	 *
	 * @param ProductRowCollection $productRows
	 *
	 * @return void
	 */
	protected function fillReservationResult(ProductRowCollection $productRows): void
	{
		$this->reservationResult ??= new ReservationResult();

		foreach ($productRows as $row)
		{
			/**
			 * @var ProductRow $row
			 */

			$rowId = (int)($row->getId() ?? 0);
			if (!$rowId)
			{
				continue;
			}
			elseif (isset($this->processedRowsIds[$rowId]))
			{
				continue;
			}

			// saving processed rows
			$this->processedRowsIds[$rowId] = true;

			/**
			 * @var ProductRowReservation|EntityObject $productReservation
			 */
			$productReservation = $row->getProductRowReservation();
			if (!$productReservation)
			{
				continue;
			}

			// if empty the store, that we don't know where create reserve.
			if (empty($productReservation->getStoreId()))
			{
				$this->deleteReservationIds[] = $productReservation->getId();
				continue;
			}

			$oldValues = $productReservation->collectValues(Values::ACTUAL);
			$oldStoreId = (int)$oldValues['STORE_ID'];
			$oldDateReserveEnd = $oldValues['DATE_RESERVE_END'] ? (string)$oldValues['DATE_RESERVE_END'] : null;
			$oldReserveQuantity = (float)$oldValues['RESERVE_QUANTITY'];

			$isNotSavedOnSale = $productReservation->getReserveId() === null;

			$newValues = $productReservation->collectValues(Values::CURRENT);
			if (empty($newValues))
			{
				$reserveInfo = $this->reservationResult->addReserveInfo(
					$rowId,
					$oldReserveQuantity,
					0
				);
				$reserveInfo->setStoreId($oldStoreId ?: null);
				$reserveInfo->setDateReserveEnd($oldDateReserveEnd ?: null);

				if ($isNotSavedOnSale)
				{
					$reserveInfo->setDeltaReserveQuantity($oldReserveQuantity);
				}
			}
			else
			{
				$newStoreId =
					array_key_exists('STORE_ID', $newValues)
						? (int)$newValues['STORE_ID']
						: $oldStoreId
				;
				$newReserveQuantity =
					array_key_exists('RESERVE_QUANTITY', $newValues)
						? (float)$newValues['RESERVE_QUANTITY']
						: $oldReserveQuantity
				;
				$newDateReserveEnd =
					array_key_exists('DATE_RESERVE_END', $newValues)
						? ($newValues['DATE_RESERVE_END'] ? (string)$newValues['DATE_RESERVE_END'] : null)
						: $oldDateReserveEnd
				;

				$reserveInfo = $this->reservationResult->addReserveInfo(
					$rowId,
					$newReserveQuantity,
					$newReserveQuantity - $oldReserveQuantity
				);
				$reserveInfo->setStoreId($newStoreId ?: null);
				$reserveInfo->setDateReserveEnd($newDateReserveEnd ?: null);

				if ($newStoreId !== $oldStoreId)
				{
					$reserveInfo->setDeltaReserveQuantity($reserveInfo->getReserveQuantity());
					$reserveInfo->setChanged();
				}
				elseif ($newDateReserveEnd !== $oldDateReserveEnd)
				{
					$reserveInfo->setChanged();
				}
			}

			if ($isNotSavedOnSale)
			{
				$reserveInfo->setChanged();
			}
		}
	}

	/**
	 * Save reserve infos.
	 *
	 * If not information for reserves, calls general reservation operation for products,
	 * to works the reservation strategies.
	 *
	 * @param int $dealId
	 *
	 * @return void
	 */
	protected function synchronizeReserves(int $dealId): void
	{
		if (!isset($this->reservationResult) || empty($this->reservationResult->getChangedReserveInfos()))
		{
			ReservationService::getInstance()->reservationProducts(CCrmOwnerType::Deal, $dealId);
		}
		else
		{
			$syncronizer = new OrderDealSynchronizer();
			$syncronizer->syncOrderReservesFromDeal($dealId, $this->reservationResult);
		}
	}
}

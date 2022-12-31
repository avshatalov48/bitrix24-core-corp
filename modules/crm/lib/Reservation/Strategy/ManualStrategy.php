<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Crm\Service\Sale\Reservation\ShipmentService;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

Loc::loadMessages(__FILE__);

/**
 * Manual change of the reserve quantity.
 *
 * If the reserve quantity is less than product quantity in entity + deducted quantity,
 * the reserve quantity change to `product quantity - deducted quantuty`.
 */
class ManualStrategy implements Strategy
{
	/**
	 * @inheritDoc
	 */
	public function reservation(int $ownerTypeId, int $ownerId): ReservationResult
	{
		return new ReservationResult();
	}

	/**
	 * @inheritDoc
	 */
	public function reservationProductRow(int $productRowId, float $quantity, int $storeId, ?Date $dateReserveEnd): ReservationResult
	{
		$result = new ReservationResult();

		$productRow = $this->getProductRow($productRowId);
		if (!$productRow)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_MANUAL_STRATEGY_PRODUCT_NOT_FOUND'))
			);
			return $result;
		}

		if (
			ReservationService::getInstance()->isRestrictedType((int)$productRow['TYPE'])
			|| (int)$productRow['PRODUCT_ID'] === 0
		)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_MANUAL_STRATEGY_PRODUCT_NOT_SUPPORT_RESERVATION'))
			);
			return $result;
		}

		$currentQuantity = $this->getRowQuantity($productRow);

		$deductedQuantity = $this->getDeductedQuantity($productRowId);
		$freeQuantity = $currentQuantity - $deductedQuantity;

		$reserveInfo = $result->addReserveInfo($productRowId, $quantity, $quantity);
		$reserveInfo->setStoreId($storeId);
		$reserveInfo->setDateReserveEnd($dateReserveEnd ? (string)$dateReserveEnd : null);

		$existReserve = ProductRowReservationTable::getRow([
			'select' => [
				'ID',
				'STORE_ID',
				'RESERVE_QUANTITY',
			],
			'filter' => [
				'=ROW_ID' => $productRowId,
			],
		]);
		if ($existReserve)
		{
			$existReserveQuantity = (float)$existReserve['RESERVE_QUANTITY'];
			if ($quantity !== $existReserveQuantity)
			{
				$delta = $quantity - $existReserveQuantity;
				if ($delta > $freeQuantity)
				{
					$quantity -= $delta - $freeQuantity;
				}

				$reserveInfo->setDeltaReserveQuantity($delta);
				$reserveInfo->setReserveQuantity($quantity);
			}
			else
			{
				$reserveInfo->setDeltaReserveQuantity(0);
			}

			if ($storeId !== (int)$existReserve['STORE_ID'])
			{
				$reserveInfo->setChanged();
			}

			$saveResult = ProductRowReservationTable::update($existReserve['ID'], [
				'RESERVE_QUANTITY' => $quantity,
				'STORE_ID' => $storeId,
				'DATE_RESERVE_END' => $dateReserveEnd,
			]);
		}
		else
		{
			$saveResult = ProductRowReservationTable::add([
				'ROW_ID' => $productRowId,
				'RESERVE_QUANTITY' => $quantity,
				'STORE_ID' => $storeId,
				'DATE_RESERVE_END' => $dateReserveEnd,
			]);
		}

		$result->addErrors(
			$saveResult->getErrors()
		);

		return $result;
	}

	/**
	 * The quantity of product row in entity.
	 *
	 * @param array $productRow
	 * @return float
	 */
	private function getRowQuantity(array $productRow): float
	{
		return (float)$productRow['QUANTITY'];
	}

	/**
	 * Get product row.
	 *
	 * @param int $rowId
	 *
	 * @return array|null
	 */
	private function getProductRow(int $rowId): ?array
	{
		return ProductRowTable::getRow([
			'select' => [
				'ID',
				'QUANTITY',
				'TYPE',
				'PRODUCT_ID',
			],
			'filter' => [
				'=ID' => $rowId,
			],
		]);
	}

	/**
	 * The deducted quantity of product row.
	 *
	 * @param int $productRowId
	 *
	 * @return float
	 */
	private function getDeductedQuantity(int $productRowId): float
	{
		$result = ShipmentService::getInstance()->getDeductedProductRowsQuantity([ $productRowId ]);
		return (float)($result[$productRowId] ?? 0.0);
	}
}

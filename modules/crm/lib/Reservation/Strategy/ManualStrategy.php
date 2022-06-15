<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\Reservation\ShipmentService;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

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
	public function reservationProductRow(int $productRowId, float $quantity, int $storeId, ?Date $dateReserveEnd): Result
	{
		$result = new Result();

		$currentQuantity = $this->getRowQuantity($productRowId);
		if (!isset($currentQuantity))
		{
			return $result;
		}

		$deductedQuantity = $this->getDeductedQuantity($productRowId);
		$quantity = min($quantity, $currentQuantity - $deductedQuantity);

		$existReserve = ProductRowReservationTable::getRow([
			'select' => [
				'ID',
			],
			'filter' => [
				'=ROW_ID' => $productRowId,
			],
		]);
		if ($existReserve)
		{
			return ProductRowReservationTable::update($existReserve['ID'], [
				'RESERVE_QUANTITY' => $quantity,
				'STORE_ID' => $storeId,
				'DATE_RESERVE_END' => $dateReserveEnd,
			]);
		}

		return ProductRowReservationTable::add([
			'ROW_ID' => $productRowId,
			'RESERVE_QUANTITY' => $quantity,
			'STORE_ID' => $storeId,
			'DATE_RESERVE_END' => $dateReserveEnd,
		]);
	}

	/**
	 * The quantity of product row in entity.
	 *
	 * @param int $productRowId
	 *
	 * @return float|null return `null` is row not found.
	 */
	private function getRowQuantity(int $productRowId): ?float
	{
		$row = ProductRowTable::getRow([
			'select' => [
				'QUANTITY',
			],
			'filter' => [
				'=ID' => $productRowId,
			],
		]);
		if ($row)
		{
			return (float)$row['QUANTITY'];
		}
		return null;
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

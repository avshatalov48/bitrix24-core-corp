<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Main\Type\Date;

/**
 * The strategy of reserving products rows.
 * Determines how and when product rows are reserved.
 */
interface Strategy
{
	/**
	 * Reservation all products of entity.
	 *
	 * @param int $entityTypeId
	 * @param int $entityId
	 *
	 * @return ReservationResult
	 */
	public function reservation(int $entityTypeId, int $entityId): ReservationResult;

	/**
	 * Reservation one concrete product row.
	 *
	 * @param int $productRowId
	 * @param float $quantity
	 * @param int $storeId
	 * @param Date|null $dateReserveEnd
	 *
	 * @return ReservationResult
	 */
	public function reservationProductRow(int $productRowId, float $quantity, int $storeId, ?Date $dateReserveEnd): ReservationResult;
}

<?php

namespace Bitrix\Crm\Reservation\Strategy\Reserve;

use Bitrix\Main\Result;

/**
 * Reservation operation result.
 *
 * It contains information about the quantity, date and store of reserved product.
 *
 * Example:
 * ```php
	$result = new \Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult();
	foreach ($rows as $row)
	{
		$rowId = (int)$row['ID'];

		$reserveInfo = $result->addReserveInfo(
			$rowId,
			$reserveQuantity,
			$reserveQuantity - $oldReserveQuantity
		);

		$reserveInfo->storeId = $row['STORE_ID'];
		$reserveInfo->dateReserveEnd = (string)$row['DATE_RESERVE_END'];
	}

	// process result
	$reserveInfos = $result->getReserves();
	foreach ($reserveInfos as $rowId => $reserveInfo)
	{
		// ...
	}
 * ```
 */
class ReservationResult extends Result
{
	/**
	 * @var ReserveInfo[] in format [rowId => ReserveInfo]
	 */
	private array $reserves = [];

	/**
	 * Reservation information.
	 *
	 * @return ReserveInfo[] in format [rowId => ReserveInfo]
	 */
	public function getReserveInfos(): array
	{
		return $this->reserves;
	}

	/**
	 * Reservation information only with changed quantites.
	 *
	 * @return ReserveInfo[]
	 */
	public function getChangedReserveInfos(): array
	{
		return array_filter(
			$this->reserves,
			fn(ReserveInfo $i) => $i->isChanged()
		);
	}

	/**
	 * Add reservation info.
	 *
	 * @param int $rowId
	 * @param float $currentQuantity
	 * @param float $deltaQuantity
	 *
	 * @return ReserveInfo
	 */
	public function addReserveInfo(int $rowId, float $currentQuantity, float $deltaQuantity): ReserveInfo
	{
		return $this->reserves[$rowId] = new ReserveInfo(
			$currentQuantity,
			$deltaQuantity
		);
	}
}

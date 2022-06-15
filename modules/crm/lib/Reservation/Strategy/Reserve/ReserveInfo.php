<?php

namespace Bitrix\Crm\Reservation\Strategy\Reserve;

/**
 * Information of reservation.
 */
class ReserveInfo
{
	public float $reserveQuantity;
	/**
	 * The difference between the old and the new quantity of reserve.
	 * It can be negative if the reserve has been withdrawn.
	 *
	 * @var float
	 */
	public float $deltaReserveQuantity;
	public ?int $storeId = null;
	public ?string $dateReserveEnd = null;

	/**
	 * @param float $reserveQuantity
	 * @param float $deltaReserveQuantity
	 */
	public function __construct(
		float $reserveQuantity,
		float $deltaReserveQuantity
	)
	{
		$this->reserveQuantity = $reserveQuantity;
		$this->deltaReserveQuantity = $deltaReserveQuantity;
	}
}

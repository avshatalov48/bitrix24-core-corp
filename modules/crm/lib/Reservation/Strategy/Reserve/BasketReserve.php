<?php

namespace Bitrix\Crm\Reservation\Strategy\Reserve;

/**
 * Basket reserve information.
 */
class BasketReserve
{
	public int $basketId;
	public ?int $reserveId = null;
	public ?float $quantity = null;

	/**
	 * @param int $basketId
	 */
	public function __construct(int $basketId)
	{
		$this->basketId = $basketId;
	}
}

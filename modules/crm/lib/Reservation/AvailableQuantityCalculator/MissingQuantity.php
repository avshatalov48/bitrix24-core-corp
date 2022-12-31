<?php

namespace Bitrix\Crm\Reservation\AvailableQuantityCalculator;

/**
 * DTO for the missing quantity.
 */
class MissingQuantity
{
	public ?int $id;
	public int $productId;
	public int $storeId;
	public float $quantity;

	/**
	 * @param int|null $id
	 * @param int $productId
	 * @param int $storeId
	 * @param float $quantity
	 */
	public function __construct(
		?int $id,
		int $productId,
		int $storeId,
		float $quantity
	)
	{
		$this->id = $id;
		$this->productId = $productId;
		$this->storeId = $storeId;
		$this->quantity = $quantity;
	}
}

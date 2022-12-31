<?php

namespace Bitrix\Crm\Reservation\AvailableQuantityCalculator;

/**
 * DTO for the need quantity.
 */
class NeedQuantity
{
	public ?int $id;
	public int $productId;
	private array $stores;

	/**
	 * @param int|null $id
	 * @param int $productId
	 * @param array $stores
	 */
	public function __construct(
		?int $id,
		int $productId,
		array $stores
	)
	{
		$this->id = $id;
		$this->productId = $productId;
		$this->stores = [];

		foreach ($stores as $storeId => $value)
		{
			$this->stores[ (int)$storeId ] = (float)$value;
		}
	}

	/**
	 * Stores
	 *
	 * @return array in format `[storeId => quantity]`
	 */
	public function getStores(): array
	{
		return $this->stores;
	}
}

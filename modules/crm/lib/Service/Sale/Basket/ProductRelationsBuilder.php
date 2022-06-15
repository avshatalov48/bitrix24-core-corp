<?php

namespace Bitrix\Crm\Service\Sale\Basket;

/**
 * Sale basket items and crm product rows relations builder.
 */
class ProductRelationsBuilder
{
	/**
	 * @var array
	 */
	private $crmProductRows = [];
	/**
	 * @var array
	 */
	private $basketItems = [];
	
	/**
	 * Add sale basket item
	 *
	 * @param int $basketId
	 * @param int $productId
	 * @param float $price
	 * @param float $quantity
	 *
	 * @return void
	 */
	public function addSaleBasketItem(int $basketId, int $productId, float $price, float $quantity): void
	{
		$this->basketItems[$basketId] = compact(
			'productId',
			'price',
			'quantity',
		);
	}
	
	/**
	 * Add crm product row.
	 *
	 * @param int $id
	 * @param int $productId
	 * @param float $price
	 * @param float $quantity
	 *
	 * @return void
	 */
	public function addCrmProductRow(int $id, int $productId, float $price, float $quantity): void
	{
		$this->crmProductRows[$id] = compact(
			'productId',
			'price',
			'quantity',
		);
	}
	
	/**
	 * Check is equal product row and basket item.
	 *
	 * @param array $row
	 * @param array $basket
	 * @param bool $strict if `true` - check all fields, else check only `productId`.
	 *
	 * @return bool
	 */
	private function isEqual(array $row, array $basket, bool $strict): bool
	{
		if ($row && $basket)
		{
			if ($strict)
			{
				return empty(
					array_diff_assoc($row, $basket)
				);
			}
			
			return $row['productId'] === $basket['productId'];
		}
		return false;
	}
	
	/**
	 * Get relations.
	 *
	 * @return array in format `['rowId' => 'basketId']`
	 */
	public function getRelations(): array
	{
		$result = [];
		$usedBasketIds = [];
		
		$steps = [
			true,
			false,
		];
		foreach ($steps as $strict)
		{
			foreach ($this->basketItems as $basketId => $basketItem)
			{
				if (isset($usedBasketIds[$basketId]))
				{
					continue;
				}
				
				foreach ($this->crmProductRows as $rowId => $crmProductRow)
				{
					if (isset($result[$rowId]))
					{
						continue;
					}
					
					if ($this->isEqual($crmProductRow, $basketItem, $strict))
					{
						$result[$rowId] = $basketId;
						$usedBasketIds[$basketId] = true;
						break;
					}
				}
			}
			
			if (count($result) === count($this->crmProductRows))
			{
				break;
			}	
		}
		
		return $result;
	}
}
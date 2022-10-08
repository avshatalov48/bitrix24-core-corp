<?php

namespace Bitrix\Crm\Service\Sale\Basket;

use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator\BasketXmlIdCompare;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator\ProductCompare;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator\ProductRowXmlIdCompare;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator\ProductWithAttributesCompare;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Item;
use InvalidArgumentException;

/**
 * Sale basket items and crm product rows relations builder.
 */
class ProductRelationsBuilder
{
	/**
	 * @var Item[]
	 */
	private array $crmProductRows = [];
	/**
	 * @var Item[]
	 */
	private array $saleBasketItems = [];
	/**
	 * @var Comparator[]
	 */
	private array $comparators = [];

	/**
	 * @param array $comparators
	 */
	public function __construct(?array $comparators = null)
	{
		$comparators ??= [
			new ProductRowXmlIdCompare(),
			new BasketXmlIdCompare(),
			new ProductWithAttributesCompare(),
			new ProductCompare(),
		];

		$this->setComparators($comparators);
	}

	/**
	 * Set comparators that will be used to compare products.
	 *
	 * @param Comparator[] $comparators
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setComparators(array $comparators): void
	{
		$this->comparators = [];

		foreach ($comparators as $comparator)
		{
			if ($comparator instanceof Comparator)
			{
				$this->comparators[] = $comparator;
			}
			else
			{
				throw new InvalidArgumentException('Parameter must be array with objects implementing Comparator class');
			}
		}
	}

	/**
	 * Add sale basket item
	 *
	 * @param int $id
	 * @param int $productId
	 * @param float $price
	 * @param float $quantity
	 * @param string $xmlId
	 *
	 * @return void
	 */
	public function addSaleBasketItem(int $id, int $productId, float $price, float $quantity, string $xmlId): void
	{
		$this->saleBasketItems[$id] = new Item($id, $productId, $price, $quantity, $xmlId);
	}

	/**
	 * Add crm product row.
	 *
	 * @param int $id
	 * @param int $productId
	 * @param float $price
	 * @param float $quantity
	 * @param string $xmlId
	 *
	 * @return void
	 */
	public function addCrmProductRow(int $id, int $productId, float $price, float $quantity, string $xmlId): void
	{
		$this->crmProductRows[$id] = new Item($id, $productId, $price, $quantity, $xmlId);
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

		foreach ($this->comparators as $comparator)
		{
			foreach ($this->saleBasketItems as $basketId => $basketItem)
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

					if ($comparator->isEqual($crmProductRow, $basketItem))
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

<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;

use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Item;

/**
 * Compares productId field of the product row with the basket item.
 */
class ProductCompare implements Comparator
{
	/**
	 * @inheritDoc
	 */
	public function isEqual(Item $productRow, Item $basketItem): bool
	{
		return $productRow->getProductId() === $basketItem->getProductId();
	}
}

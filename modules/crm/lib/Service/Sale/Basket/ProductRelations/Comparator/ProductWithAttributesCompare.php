<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;

use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Item;

/**
 * Compares productId, quantity and price fields of the product row with the basket item.
 */
class ProductWithAttributesCompare implements Comparator
{
	/**
	 * @inheritDoc
	 */
	public function isEqual(Item $productRow, Item $basketItem): bool
	{
		return
			$productRow->getProductId() === $basketItem->getProductId()
			&& $productRow->getQuantity() === $basketItem->getQuantity()
			&& $productRow->getPrice() === $basketItem->getPrice()
		;
	}
}

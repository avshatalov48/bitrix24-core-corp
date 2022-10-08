<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations;

/**
 * Object for compare the product row and the basket item.
 */
interface Comparator
{
	/**
	 * Comparison according to a given logic.
	 *
	 * @param Item $productRow
	 * @param Item $basketItem
	 *
	 * @return bool TRUE if items are equals.
	 */
	public function isEqual(Item $productRow, Item $basketItem): bool;
}

<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;

use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Item;

/**
 * Compares the product row's `id` with the basket item's `xmlId`.
 */
class BasketXmlIdCompare implements Comparator
{
	/**
	 * @inheritDoc
	 */
	public function isEqual(Item $productRow, Item $basketItem): bool
	{
		$rowId = BasketXmlId::getRowIdFromXmlId($basketItem->getXmlId());
		if (isset($rowId))
		{
			return $rowId === $productRow->getId();
		}

		return false;
	}
}

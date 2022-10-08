<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;

use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Comparator;
use Bitrix\Crm\Service\Sale\Basket\ProductRelations\Item;

/**
 * Compares the basket item's `id` with the product row's `xmlId`.
 */
class ProductRowXmlIdCompare implements Comparator
{
	/**
	 * @inheritDoc
	 */
	public function isEqual(Item $productRow, Item $basketItem): bool
	{
		$basketItemId = ProductRowXmlId::getBasketIdFromXmlId($productRow->getXmlId());
		if (isset($basketItemId))
		{
			return $basketItemId === $basketItem->getId();
		}

		return false;
	}
}

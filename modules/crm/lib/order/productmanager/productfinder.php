<?php

namespace Bitrix\Crm\Order\ProductManager;

use Bitrix\Crm;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId;

trait ProductFinder
{
	abstract protected function getOrder(): ?Crm\Order\Order;

	/**
	 * Get basket items from order
	 *
	 * @param array $product
	 * @param array $foundProducts array with history founded items
	 * @return Crm\Order\BasketItem|null
	 */
	protected function getBasketItemByEntityProduct(array $product, array & $foundProducts, bool $checkQuantity = false):? Crm\Order\BasketItem
	{
		if (!$this->getOrder())
		{
			return null;
		}

		/** @var Crm\Order\BasketItem $basketItem */
		foreach ($this->getOrder()->getBasket() as $basketItem)
		{
			if (
				$basketItem->getProductId() === (int)$product['PRODUCT_ID']
				&& $basketItem->getField('MODULE') === $product['MODULE']
				&& !in_array($basketItem->getBasketCode(), $foundProducts, true)
			)
			{
				if (isset($product['RESERVE']))
				{
					$reserve = null;
					$productReserveId = (int)current(array_keys($product['RESERVE']));

					$reserveCollection = $basketItem->getReserveQuantityCollection();
					if ($reserveCollection)
					{
						$reserve = array_filter($reserveCollection->toArray(), static function ($reserveItem) use ($productReserveId) {
							return (int)$reserveItem['ID'] === $productReserveId;
						});
					}

					if (!$reserve)
					{
						continue;
					}
				}

				if ($checkQuantity && $basketItem->getQuantity() !== (float)$product['QUANTITY'])
				{
					continue;
				}

				$foundProducts[] = $basketItem->getBasketCode();
				return $basketItem;
			}
		}

		return null;
	}

	/**
	 * @param array $searchableProduct
	 * @param array $productList
	 * @return false|int|string
	 */
	protected static function searchProduct(array $searchableProduct, array $productList)
	{
		if ((int)$searchableProduct['PRODUCT_ID'] === 0)
		{
			return false;
		}

		static $foundProducts = [];

		foreach ($productList as $index => $item)
		{
			if (
				(int)$searchableProduct['PRODUCT_ID'] === (int)$item['PRODUCT_ID']
				&& $searchableProduct['MODULE'] === $item['MODULE']
				&& !in_array($item['BASKET_CODE'], $foundProducts, true)
			)
			{
				$foundProducts[] = $item['BASKET_CODE'];
				return $index;
			}
		}

		return false;
	}

	/**
	 * Search all item indexes with productId
	 *
	 * If there are duplicate products in the basket, then each of them will be used
	 * exactly once (the $usedIndexes parameter is responsible for this).
	 *
	 * @param array $productList
	 * @param int $productId
	 * @param array $usedIndexes accumulates used basket items in the format ['productID' => ['basketId1', 'basketId2', ...]].
	 * In general, you can send a variable with an empty array, as the method is used, it will be filled in.
	 *
	 * @return false|int|string
	 */
	protected function searchProductById(array $productList, int $productId, array & $usedIndexes)
	{
		if ($productId === 0)
		{
			return false;
		}

		$usedIndexes[$productId] ??= [];
		foreach ($productList as $index => $item)
		{
			if (in_array($index, $usedIndexes[$productId], true))
			{
				continue;
			}

			if ($productId === (int)$item['PRODUCT_ID'])
			{
				$usedIndexes[$productId][] = $index;
				return $index;
			}
		}

		return false;
	}

	/**
	 * Search entity product by basket item id.
	 *
	 * @param array $productList
	 * @param int $basketId
	 * @param array $usedIndexes accumulates used basket items in the format ['productID' => ['basketId1', 'basketId2', ...]].
	 * In general, you can send a variable with an empty array, as the method is used, it will be filled in.
	 *
	 * @return false|int
	 */
	protected function searchProductByBasketId(array $productList, int $basketId, array & $usedIndexes)
	{
		if ($basketId === 0)
		{
			return false;
		}

		$xmlId = ProductRowXmlId::getXmlIdFromBasketId($basketId);
		foreach ($productList as $index => $item)
		{
			$productId = (int)($item['PRODUCT_ID'] ?? 0);
			$usedIndexes[$productId] ??= [];
			if (in_array($index, $usedIndexes[$productId], true))
			{
				continue;
			}

			if ($xmlId === (string)$item['XML_ID'])
			{
				$usedIndexes[$productId][] = $index;
				return $index;
			}
		}

		return false;
	}
}

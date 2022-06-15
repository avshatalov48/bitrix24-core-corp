<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

final class BasketReservation
{
	/**
	 * Adds reservation info to map
	 *
	 * @param Main\Event $event
	 */
	public static function OnSaleOrderSaved(Main\Event $event)
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');
		if (!($order instanceof Crm\Order\Order))
		{
			return;
		}

		$binding = $order->getEntityBinding();
		$entityTypeId = $binding ? $binding->getOwnerTypeId() : 0;
		$entityId = $binding ? $binding->getOwnerId() : 0;
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		$productRows = self::getProductRow($entityTypeId, $entityId);
		if (!$productRows)
		{
			return;
		}

		/** @var Crm\Order\Basket $basket */
		$basket = $order->getBasket();

		$reservationMap = self::getProductReservationMap($basket);

		/** @var Crm\Order\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			/** @var Sale\ReserveQuantity $reserveQuantity */
			foreach ($basketItem->getReserveQuantityCollection() as $reserveQuantity)
			{
				$foundProduct = self::findProduct($productRows, $basketItem, $reserveQuantity);
				if ($foundProduct)
				{
					if ($reservationMap && self::isReserveExists($reservationMap, $reserveQuantity->getId(), $foundProduct))
					{
						continue;
					}

					self::saveProductReservationMap($foundProduct['ID'], $reserveQuantity->getId());
					self::saveProductReservation($foundProduct['ID'], $reserveQuantity);
				}
			}
		}
	}

	/**
	 * Deletes reservation info from map
	 *
	 * @param Main\Event $event
	 */
	public static function onAfterDelete(Main\Event $event)
	{
		$reservationId = (int)$event->getParameter('id')['ID'];
		if ($reservationId > 0)
		{
			self::deleteProductReservationMap($reservationId);
		}
	}

	private static function findProduct(array $productRows, Sale\BasketItem $basketItem, Sale\ReserveQuantity $reserveQuantity): ?array
	{
		static $foundProducts = [];

		$productId = $basketItem->getProductId();
		$quantity = $reserveQuantity->getQuantity();
		$reserveId = $reserveQuantity->getId();

		$product = array_filter(
			$productRows,
			static function ($product) use ($foundProducts, $productId, $quantity, $reserveId) {
				$rowId = (int)$product['ID'];
				$productQuantity = $product['RESERVE_QUANTITY'] ?? $product['QUANTITY'];

				if (!empty($product['RESERVE_ID']))
				{
					return (int)$product['RESERVE_ID'] === $reserveId;
				}

				return
					(int)$product['PRODUCT_ID'] === $productId
					&& (float)$productQuantity === $quantity
					&& !in_array($rowId, $foundProducts, true)
				;
			}
		);

		$foundProduct = $product ? current($product) : null;
		if ($foundProduct)
		{
			$foundProducts[] = (int)$foundProduct['ID'];
		}

		return $foundProduct;
	}

	private static function saveProductReservationMap(int $productRowId, int $reservationId): void
	{
		Crm\Reservation\Internals\ProductReservationMapTable::add([
			'PRODUCT_ROW_ID' => $productRowId,
			'BASKET_RESERVATION_ID' => $reservationId,
		]);
	}

	private static function saveProductReservation(int $productRowId, Sale\ReserveQuantity $reserveQuantity): void
	{
		$productRowReservation = Crm\Reservation\Internals\ProductRowReservationTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ROW_ID' => $productRowId,
			],
		])->fetch();

		if (!$productRowReservation)
		{
			Crm\Reservation\Internals\ProductRowReservationTable::add([
				'ROW_ID' => $productRowId,
				'RESERVE_QUANTITY' => $reserveQuantity->getQuantity(),
				'DATE_RESERVE_END' => $reserveQuantity->getField('DATE_RESERVE_END'),
				'STORE_ID' => $reserveQuantity->getStoreId(),
			]);
		}
	}

	private static function deleteProductReservationMap(int $reservationId): void
	{
		$productReservationMapIterator = Crm\Reservation\Internals\ProductReservationMapTable::getList([
			'select' => ['ID', 'PRODUCT_ROW_ID'],
			'filter' => [
				'=BASKET_RESERVATION_ID' => $reservationId,
			],
		]);
		while ($productReservationMapData = $productReservationMapIterator->fetch())
		{
			Crm\Reservation\Internals\ProductReservationMapTable::delete($productReservationMapData['ID']);
		}
	}

	private static function getProductReservationMap(Crm\Order\Basket $basket): array
	{
		$result = [];

		$basketReservationIdList = [];
		/** @var Crm\Order\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			/** @var Sale\ReserveQuantity $reserveQuantity */
			foreach ($basketItem->getReserveQuantityCollection() as $reserveQuantity)
			{
				$basketReservationIdList[] = $reserveQuantity->getId();
			}
		}

		if (!$basketReservationIdList)
		{
			return $result;
		}

		$productReservationMapIterator = Crm\Reservation\Internals\ProductReservationMapTable::getList([
			'select' => ['PRODUCT_ROW_ID', 'BASKET_RESERVATION_ID'],
			'filter' => [
				'=BASKET_RESERVATION_ID' => $basketReservationIdList,
			],
		]);
		while ($productReservationMapData = $productReservationMapIterator->fetch())
		{
			$productRowId = (int)$productReservationMapData['PRODUCT_ROW_ID'];
			$basketReservationId = (int)$productReservationMapData['BASKET_RESERVATION_ID'];

			$result[$productRowId] = $basketReservationId;
		}

		return $result;
	}

	private static function isReserveExists(array $reservationMap, int $reserveId, array $product): bool
	{
		$reserve = array_filter(
			$reservationMap,
			static function ($basketReservationId, $productRowId) use ($reserveId, $product) {
				return $basketReservationId === $reserveId && $productRowId === (int)$product['ID'];
			},
			ARRAY_FILTER_USE_BOTH
		);

		return (bool)$reserve;
	}

	private static function getProductRow(int $entityTypeId, int $entityId): array
	{
		$productRows = Crm\Reservation\DealProductsHitDataSupplement::getInstance()
			->getSupplementedProductRows($entityId)
		;

		if ($productRows)
		{
			$productsWithoutId = array_filter($productRows, static function ($productRow) {
				return (!isset($productRow['ID']) || (int)$productRow['ID'] <= 0);
			});

			if ($productsWithoutId)
			{
				$productRows = self::loadProductRows($entityTypeId, $entityId);
			}
		}
		else
		{
			$productRows = self::loadProductRows($entityTypeId, $entityId);
		}

		return $productRows;
	}

	private static function loadProductRows(int $entityTypeId, int $entityId): array
	{
		static $products = [];

		$cacheKey = $entityTypeId . '_' . $entityId;
		if (!empty($products[$cacheKey]))
		{
			return $products[$cacheKey];
		}

		$ownerTypeName = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
		$rows = \CCrmProductRow::LoadRows($ownerTypeName, $entityId);
		if (!$rows)
		{
			$products[$cacheKey] = [];

			return $products[$cacheKey];
		}

		$basketReservation = new \Bitrix\Crm\Reservation\BasketReservation();
		foreach ($rows as $row)
		{
			$basketReservation->addProduct($row);
		}

		$reservedProducts = $basketReservation->getReservedProducts();
		if ($reservedProducts)
		{
			foreach ($rows as $index => $row)
			{
				$reservedProductData = $reservedProducts[$row['ID']] ?? null;
				if ($reservedProductData)
				{
					$rows[$index] = array_merge($row, $reservedProductData);
				}
			}
		}

		$products[$cacheKey] = $rows;

		return $products[$cacheKey];
	}
}

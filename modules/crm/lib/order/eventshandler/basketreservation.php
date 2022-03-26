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

		/** @var Crm\Order\Basket $basket */
		$basket = $order->getBasket();

		$productRows = Crm\Reservation\DealProductsHitDataSupplement::getInstance()
			->getSupplementedProductRows($entityId)
		;
		if (!$productRows)
		{
			$productRows = self::loadProductRows($entityTypeId, $entityId);
		}

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

	private static function saveProductReservationMap(int $productId, int $reservationId): void
	{
		Crm\Reservation\Internals\ProductReservationMapTable::add([
			'PRODUCT_ROW_ID' => $productId,
			'BASKET_RESERVATION_ID' => $reservationId,
		]);
	}

	private static function deleteProductReservationMap(int $reservationId): void
	{
		$productReservationMapIterator = Crm\Reservation\Internals\ProductReservationMapTable::getList([
			'select' => ['ID'],
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

	private static function loadProductRows(int $entityTypeId, int $entityId): array
	{
		static $products = [];

		$cacheKey = $entityTypeId . '_' . $entityId;
		if (!empty($products[$cacheKey]))
		{
			return $products[$cacheKey];
		}

		$ownerTypeName = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
		$products[$cacheKey] = \CCrmProductRow::LoadRows($ownerTypeName, $entityId);

		return $products[$cacheKey];
	}
}
<?php

namespace Bitrix\Crm\Service\Sale\Reservation;

use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Crm\Service\Sale\Shipment\ProductService;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Internals\ShipmentItemTable;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentItem;
use CCrmOwnerType;

/**
 * Service for work with reserves of shipments.
 */
class ShipmentService
{
	private BasketService $basketService;
	private ProductService $shipmentProductService;

	/**
	 * @param BasketService $basketService
	 * @param ProductService $shipmentProductService
	 */
	public function __construct(
		BasketService $basketService,
		ProductService $shipmentProductService
	)
	{
		Loader::requireModule('sale');

		$this->basketService = $basketService;
		$this->shipmentProductService = $shipmentProductService;
	}

	/**
	 * Service instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return ServiceLocator::getInstance()->get('crm.reservation.shipment');
	}

	/**
	 * Shipment owner.
	 *
	 * Example:
	 * ```php
		$result = $this->getShipmentOwner($shipment);
		if (!$result->isSuccess())
		{
			return $result;
		}
		[$entityTypeId, $entityId] = $result->getData();
	 * ```
	 *
	 * @param Shipment $shipment
	 *
	 * @return Result with errors, or with owner entity type id and id.
	 */
	private function getShipmentOwner(Shipment $shipment): Result
	{
		$result = new Result();

		$order = $shipment->getOrder();
		if (!$order)
		{
			$result->addError(
				new Error('Shipment without order')
			);
			return $result;
		}
		elseif (!($order instanceof \Bitrix\Crm\Order\Order))
		{
			$result->addError(
				new Error('Is not crm order')
			);
			return $result;
		}

		/**
		 * @var \Bitrix\Crm\Order\Order $order
		 */

		$entityBinding = $order->getEntityBinding();
		if (!$entityBinding)
		{
			$result->addError(
				new Error('Order is not binded to crm entity')
			);
			return $result;
		}

		$result->setData([
			$entityBinding->getOwnerTypeId(),
			$entityBinding->getOwnerId(),
		]);
		return $result;
	}

	/**
	 * Reserve shipped quantity.
	 *
	 * @param Shipment $shipment
	 *
	 * @return Result
	 */
	public function reserveCanceledShipment(Shipment $shipment): Result
	{
		$result = new Result();

		$order = $shipment->getOrder();
		if (!$order)
		{
			$result->addError(
				new Error('Shipment without order cannot processed.')
			);
			return $result;
		}

		$result = $this->getShipmentOwner($shipment);
		if (!$result->isSuccess())
		{
			return $result;
		}
		[$entityTypeId, $entityId] = $result->getData();

		// only for deal sync
		if ($entityTypeId !== CCrmOwnerType::Deal)
		{
			return $result;
		}

		$basketItems = [];
		foreach ($shipment->getShipmentItemCollection() as $item)
		{
			/**
			 * @var ShipmentItem $item
			 */

			$basketItem = $item->getBasketItem();
			if ($basketItem)
			{
				$basketItems[$basketItem->getId()] = $basketItem;
			}
		}

		$shippedBasketItemsIds = array_keys($basketItems);
		if (empty($shippedBasketItemsIds))
		{
			return $result;
		}

		$productRow2basket = $this->basketService->getRowIdsToBasketIdsByEntity(
			$entityTypeId,
			$entityId
		);
		if (empty($productRow2basket))
		{
			return $result;
		}
		$productRow2basket = array_filter($productRow2basket, fn($basketId) => in_array($basketId, $shippedBasketItemsIds, true));
		$shippedQuantities = $this->shipmentProductService->getShippedQuantityByRowBasketMap($productRow2basket);

		$rowReserves = ProductRowReservationTable::getList([
			'select' => [
				'ID',
				'ROW_ID',
				'STORE_ID',
				'DATE_RESERVE_END',
				'RESERVE_QUANTITY',
			],
			'filter' => [
				'=ROW_ID' => array_keys($productRow2basket),
			],
		]);
		$rowReserves = array_column($rowReserves->fetchAll(), null, 'ROW_ID');

		$reservationResult = new ReservationResult();
		foreach ($productRow2basket as $rowId => $basketId)
		{
			$rowReserve = $rowReserves[$rowId] ?? null;
			if (!isset($rowReserve))
			{
				continue;
			}

			/**
			 * @var BasketItem $basketItem
			 */
			$basketItem = $basketItems[$basketId] ?? null;
			if (!$basketItem || !$basketItem->isReservableItem())
			{
				continue;
			}

			$crmReserveQuantity = (float)$rowReserve['RESERVE_QUANTITY'];
			if (empty($crmReserveQuantity))
			{
				continue;
			}

			$basketReserveQuantity = (float)$basketItem->getReservedQuantity();
			$shippedQuantity = (float)($shippedQuantities[$rowId] ?? 0.0);

			$currentQuantity = $crmReserveQuantity;
			$deltaQuantity = $crmReserveQuantity - $basketReserveQuantity - $shippedQuantity;
			if ($deltaQuantity <= 0.0)
			{
				continue;
			}

			$reserveInfo = $reservationResult->addReserveInfo(
				$rowId,
				$currentQuantity,
				$deltaQuantity
			);

			if (isset($rowReserve['STORE_ID']))
			{
				$reserveInfo->setStoreId((int)$rowReserve['STORE_ID']);
			}

			if (isset($rowReserve['DATE_RESERVE_END']))
			{
				$reserveInfo->setDateReserveEnd((string)$rowReserve['DATE_RESERVE_END']);
			}
		}

		if (!empty($reservationResult->getChangedReserveInfos()))
		{
			$synchronizer = new OrderDealSynchronizer(true);
			$synchronizer->syncOrderReservesFromDeal($entityId, $reservationResult);
		}

		return $result;
	}

	/**
	 * The quantity of product rows deducted.
	 *
	 * @param array $rowsIds
	 *
	 * @return array in format `[rowId => deductedQuantity]`
	 */
	public function getDeductedProductRowsQuantity(array $rowsIds): array
	{
		$result = [];

		$productRowToBasket = $this->basketService->getRowIdsToBasketIdsByRows($rowsIds);
		if (empty($productRowToBasket))
		{
			return $result;
		}

		$deductedBasketItems = ShipmentItemTable::getList([
			'select' => [
				'BASKET_ID',
				'QUANTITY',
			],
			'filter' => [
				'=BASKET_ID' => array_values($productRowToBasket),
				'=DELIVERY.DEDUCTED' => 'Y',
			],
		]);
		$deductedBasketItems = array_column($deductedBasketItems->fetchAll(), 'QUANTITY', 'BASKET_ID');

		foreach ($productRowToBasket as $rowId => $basketId)
		{
			$result[$rowId] = (float)($deductedBasketItems[$basketId] ?? 0.0);
		}

		return $result;
	}
}

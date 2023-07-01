<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products\Reservation;

use Bitrix\Crm\Reservation\Internals\ProductReservationMapTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Main\Result;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Bitrix\Sale\ReserveQuantity;
use CCrmOwnerType;
use DomainException;

/**
 * Synchronize the reserves of deal products with the basket items reserves.
 */
class ProductRowReservesSynchronizer
{
	private int $dealId;
	private Basket $basket;
	private ReservationResult $reservationResult;

	/**
	 * @param int $dealId
	 * @param Basket $basket
	 * @param ReservationResult $reservationResult
	 */
	public function __construct(int $dealId, Basket $basket, ReservationResult $reservationResult)
	{
		$this->dealId = $dealId;
		$this->basket = $basket;
		$this->reservationResult = $reservationResult;
	}

	/**
	 * @return int[] in format `[rowId => basketId]`
	 */
	protected function getProductRowsToBasketItemsMap(): array
	{
		return BasketService::getInstance()->getRowIdsToBasketIdsByEntity(
			CCrmOwnerType::Deal,
			$this->dealId
		);
	}

	/**
	 * Sync the product rows with the reserve quantity of the basket item.
	 *
	 * @return array in format [$isNeedSave, [`rowId` => `reserveQuantity`]]
	 * Variable `isNeedSave` may be true, with empty map of product row reserves, when reserves is deleted.
	 */
	public function sync(): array
	{
		$reservedProductRows = $this->reservationResult->getChangedReserveInfos();
		if (empty($reservedProductRows))
		{
			return [false, []];
		}

		$productRowToBasket = $this->getProductRowsToBasketItemsMap();
		if (empty($productRowToBasket))
		{
			return [false, []];
		}

		$isNeedSave = false;
		$productRowReserveToBasket = [];

		foreach ($reservedProductRows as $rowId => $reserveInfo)
		{
			$basketId = $productRowToBasket[$rowId] ?? null;
			if (!$basketId)
			{
				continue;
			}

			/**
			 * @var BasketItem $basketItem
			 */
			$basketItem = $this->basket->getItemById($basketId);
			if (!$basketItem)
			{
				continue;
			}

			$basketReserveCollection = $basketItem->getReserveQuantityCollection();
			if (!$basketReserveCollection)
			{
				continue;
			}

			$isNewBasketReserve = false;

			$basketReserve = $basketReserveCollection->current();
			if (!$basketReserve)
			{
				$basketReserve = $basketReserveCollection->create();
				$isNewBasketReserve = true;
			}

			$quantity = (float)$basketReserve->getField('QUANTITY') + $reserveInfo->getDeltaReserveQuantity();
			if ($quantity < 0)
			{
				if (!$isNewBasketReserve)
				{
					$isNeedSave = true;
				}

				$basketReserve->delete();
				continue;
			}

			// if change store - delete old reserve, and create new, `ReserveQuantity` will not allow to change the store id.
			if (!$isNewBasketReserve && $reserveInfo->getStoreId() !== (int)$basketReserve->getField('STORE_ID'))
			{
				$basketReserve->delete();

				$basketReserve = $basketReserveCollection->create();
				$quantity = $reserveInfo->getReserveQuantity();
			}

			// checking and subtracting the shipped quantity
			$order = $this->basket->getOrder();
			if ($order instanceof Order)
			{
				$shippedQuantity = $order->getShipmentCollection()->getBasketItemShippedQuantity($basketItem);
				$quantity = min($quantity, $basketItem->getQuantity() - $shippedQuantity);
			}

			$fields = [
				'STORE_ID' => $reserveInfo->getStoreId(),
				'DATE_RESERVE_END' => $reserveInfo->getDateReserveEndAsDateTime(),
				'QUANTITY' => $quantity,
			];
			if (!$basketReserve->setFields($fields)->isSuccess())
			{
				$basketReserve->setFieldsNoDemand($fields);
			}

			$productRowReserveToBasket[$rowId] = $basketReserve;
			$isNeedSave = true;
		}

		return [$isNeedSave, $productRowReserveToBasket];
	}

	/**
	 * Sync and save the product rows with the reserve quantity of the basket item.
	 *
	 * For more details see `sync` method.
	 *
	 * @see ::sync
	 *
	 * @return Result
	 */
	public function syncAndSave(): Result
	{
		$result = new Result();

		[$isNeedSave, $productRowReserveToBasket] = $this->sync();
		if (!$isNeedSave && !$productRowReserveToBasket)
		{
			return $result;
		}

		$order = $this->basket->getOrder();
		if (!$order)
		{
			throw new DomainException('Synchronizer cannot save order because basket without order');
		}

		$result = $order->save();
		if ($result->isSuccess())
		{
			foreach ($productRowReserveToBasket as $rowId => $basketReserve)
			{
				/**
				 * @var ReserveQuantity $basketReserve
				 */

				$reserveId = $basketReserve->getId();
				if ($reserveId > 0)
				{
					$this->saveReserveMap($rowId, $reserveId);
				}
			}
		}

		return $result;
	}

	/**
	 * Set link between product row and basket reservation.
	 *
	 * @param int $rowId
	 * @param int $basketReservationId
	 *
	 * @return Result
	 */
	private function saveReserveMap(int $rowId, int $basketReservationId): Result
	{
		$exist = ProductReservationMapTable::getRow([
			'filter' => [
				'=PRODUCT_ROW_ID' => $rowId,
			],
		]);
		if ($exist)
		{
			if ((int)$exist['BASKET_RESERVATION_ID'] === $basketReservationId)
			{
				return new Result();
			}

			return ProductReservationMapTable::update($exist['ID'], [
				'BASKET_RESERVATION_ID' => $basketReservationId,
			]);
		}

		return ProductReservationMapTable::add([
			'PRODUCT_ROW_ID' => $rowId,
			'BASKET_RESERVATION_ID' => $basketReservationId,
		]);
	}
}

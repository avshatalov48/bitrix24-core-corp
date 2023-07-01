<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale;

/**
 * Class Shipment
 * @package Bitrix\Crm\Order\EventsHandler
 * @internal
 */
final class Shipment
{
	private static $dealId;
	private static $needSynchronizeProductRows = false;

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnSaleShipmentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');

		if (!$shipment instanceof Crm\Order\Shipment)
		{
		 	return;
		}

		if (!$shipment->getFields()->isChanged('PRICE_DELIVERY'))
		{
			return;
		}

		/** @var Crm\Order\EntityBinding $binding */
		$binding = $shipment->getOrder()->getEntityBinding();
		if (
			!$binding
			|| $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal
		)
		{
			return;
		}

		$dealId = $binding->getOwnerId();
		if ($dealId === 0)
		{
			return;
		}

		\CCrmDeal::SynchronizeProductRows($dealId);
	}

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnBeforeSaleShipmentDeleted(Main\Event $event): void
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return;
		}

		$values = $event->getParameter('VALUES');

		$shipmentId = $values['ID'] ?? null;
		if ($shipmentId)
		{
			$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($shipmentId);
			if (!$shipment || $shipment->getPrice() <= 0)
			{
				return;
			}

			/** @var Crm\Order\EntityBinding $binding */
			$binding = $shipment->getOrder()->getEntityBinding();
			if (
				!$binding
				|| $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal
			)
			{
				return;
			}

			$dealId = $binding->getOwnerId();
			if ($dealId === 0)
			{
				return;
			}

			self::$needSynchronizeProductRows = true;
			self::$dealId = $dealId;
		}
	}

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnSaleShipmentDeleted(Main\Event $event): void
	{
		if (self::$needSynchronizeProductRows && self::$dealId)
		{
			\CCrmDeal::SynchronizeProductRows(self::$dealId);

			self::$needSynchronizeProductRows = false;
			self::$dealId = null;
		}
	}

	public static function onBeforeSetField(Main\Event $event): Main\EventResult
	{
		$shipment = $event->getParameter('ENTITY');
		if (!($shipment instanceof Crm\Order\Shipment))
		{
			return new Main\EventResult(Main\EventResult::SUCCESS);
		}

		$errorCollection = new Main\ErrorCollection();

		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');

		if ($name === 'DEDUCTED' && $value === 'Y')
		{
			/** @var Sale\Reservation\BasketReservationService $basketReservation */
			$basketReservation = Main\DI\ServiceLocator::getInstance()->get('sale.basketReservation');

			/** @var Crm\Order\ShipmentItem $item */
			foreach ($shipment->getShipmentItemCollection()->getShippableItems() as $item)
			{
				$basketItem = $item->getBasketItem();
				if (!$basketItem->isReservableItem())
				{
					continue;
				}

				/** @var Crm\Order\ShipmentItemCollection $shipmentItemStoreCollection */
				$shipmentItemStoreCollection = $item->getShipmentItemStoreCollection();
				if (!$shipmentItemStoreCollection)
				{
					continue;
				}

				$quantityByStore = [];

				/** @var Crm\Order\ShipmentItemStore $store */
				foreach ($shipmentItemStoreCollection as $store)
				{
					$quantityByStore[$store->getStoreId()] ??= 0;
					$quantityByStore[$store->getStoreId()] += $store->getQuantity();
				}

				$basketCode = $basketItem->getBasketCode();
				$productId = $basketItem->getProductId();

				foreach ($quantityByStore as $storeId => $quantity)
				{
					$availableQuantity = $quantity;

					if ((int)$basketCode > 0)
					{
						$availableQuantity = $basketReservation->getAvailableCountForBasketItem(
							(int)$basketCode,
							$storeId
						);
					}
					else
					{
						$storeQuantityRow = Catalog\StoreProductTable::getRow([
							'select' => [
								'AMOUNT',
								'QUANTITY_RESERVED',
							],
							'filter' => [
								'=STORE_ID' => $storeId,
								'=PRODUCT_ID' => $productId,
							],
						]);

						if ($storeQuantityRow)
						{
							$availableQuantity = min(
								$quantity,
								$storeQuantityRow['AMOUNT'] - $storeQuantityRow['QUANTITY_RESERVED']
							);
						}
					}

					if ($quantity > $availableQuantity)
					{
						$errorCode = 'CRM_REALIZATION_NOT_ENOUGH_PRODUCTS';
						$errorMessage = Main\Localization\Loc::getMessage(
							'CRM_SHIPMENT_EVENTS_PRODUCT_QUANTITY_ERROR',
							[
								'#PRODUCT_NAME#' => $basketItem->getField('NAME'),
								'#PRODUCT_ID#' => $productId,
								'#STORE_NAME#' => \CCatalogStoreControlUtil::getStoreName($storeId),
								'#STORE_ID#' => $storeId,
							]
						);
						$errorCollection->add([new Main\Error($errorMessage, $errorCode)]);
					}
				}
			}
		}

		if (!$errorCollection->isEmpty())
		{
			$errorMessages = [];
			/** @var Main\Error $error */
			foreach ($errorCollection->getValues() as $error)
			{
				$errorMessages[] = $error->getMessage();
			}

			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(
					implode('<br>', $errorMessages),
					'CRM_REALIZATION_NOT_ENOUGH_PRODUCTS'
				)
			);
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}
}

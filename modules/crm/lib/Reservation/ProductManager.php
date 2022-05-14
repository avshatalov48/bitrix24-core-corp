<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;

class ProductManager extends Crm\Order\ProductManager
{
	/**
	 * Adds entity products to order shipment
	 *
	 * @param array $entityProducts
	 * @return Main\Result
	 */
	public function addEntityProductsToOrderForShip(array $entityProducts): Main\Result
	{
		$result = new Main\Result();

		if (!$this->getOrder())
		{
			$result->addError(new Main\Error('Order not found'));
			return $result;
		}

		$basket = $this->getOrder()->getBasket();
		if (!$basket)
		{
			$result->addError(new Main\Error('Basket not found'));
			return $result;
		}

		foreach ($entityProducts as $productId => $entityProduct)
		{
			$entityProducts[$productId]['PRODUCT'] = $this->convertToSaleBasketFormat($entityProduct['PRODUCT']);
		}

		if ($entityProducts)
		{
			$currency = $this->getOrder()->getCurrency();

			/** @var Crm\Order\BasketItem[] $basketItems */
			$basketItems = [];
			foreach ($entityProducts as $product)
			{
				$productData = $product['PRODUCT'];

				$shipmentItem = $this->getBasketItemByEntityProduct($productData);
				if (!$shipmentItem)
				{
					$shipmentItem = $basket->createItem('catalog', $productData['PRODUCT_ID']);
				}

				$setFieldsResult = $shipmentItem->setFields([
					'LID' => SITE_ID,
					'NAME' => $productData['NAME'],
					'QUANTITY' => $product['QUANTITY'],
					'CURRENCY' => $currency,
					'BASE_PRICE' => $productData['BASE_PRICE'],
					'PRICE' => $productData['PRICE'],
					'CUSTOM_PRICE' => 'Y',
					'MEASURE_CODE' => $productData['MEASURE_CODE'],
					'MEASURE_NAME' => $productData['MEASURE_NAME'],
					'PRODUCT_PROVIDER_CLASS' => '\\' . Catalog\Product\CatalogProvider::class,
				]);

				if ($setFieldsResult->isSuccess())
				{
					$basketItems[] = $shipmentItem;
				}
				else
				{
					$result->addErrors($setFieldsResult->getErrors());
				}
			}

			if (!$result->isSuccess())
			{
				return $result;
			}

			$shipmentCollection = $this->getOrder()->getShipmentCollection();

			/** @var Crm\Order\Shipment $newShipment */
			$newShipment = $shipmentCollection->createItem();

			// set RESPONSIBLE_ID
			$binding = $this->getOrder()->getEntityBinding();
			if ($binding)
			{
				$factory = Crm\Service\Container::getInstance()->getFactory($binding->getOwnerTypeId());
				if ($factory)
				{
					$item = $factory->getItem($binding->getOwnerId());
					if ($item)
					{
						$newShipment->setFieldNoDemand('RESPONSIBLE_ID', $item->getAssignedById());
					}
				}
			}

			/** @var Crm\Order\ShipmentItemCollection $newShipmentItemCollection */
			$newShipmentItemCollection = $newShipment->getShipmentItemCollection();

			foreach ($basketItems as $basketItem)
			{
				$storeList = $entityProducts[$basketItem->getProductId()]['STORE_LIST'] ?? [];

				/** @var Crm\Order\ShipmentItem $shipmentItem */
				$shipmentItem = $newShipmentItemCollection->createItem($basketItem);

				$shipmentItemQuantity = array_sum($storeList);
				$setQuantityResult = $shipmentItem->setQuantity($shipmentItemQuantity);
				if (!$setQuantityResult->isSuccess())
				{
					$result->addErrors($setQuantityResult->getErrors());
					continue;
				}

				/** @var Crm\Order\ShipmentItemStoreCollection $shipmentItemStoreCollection */
				$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();

				foreach ($storeList as $storeId => $storeQuantity)
				{
					$fields = [
						'BASKET_ID' => $basketItem->getId(),
						'STORE_ID' => $storeId,
						'QUANTITY' => $storeQuantity,
						'ORDER_DELIVERY_BASKET_ID' => $shipmentItem->getId(),
					];
					$shipmentItemStore = $shipmentItemStoreCollection->createItem($basketItem);
					$setFieldResult = $shipmentItemStore->setFields($fields);
					if (!$setFieldResult->isSuccess())
					{
						$result->addErrors($setFieldResult->getErrors());
					}
				}
			}
		}

		return $result;
	}
}
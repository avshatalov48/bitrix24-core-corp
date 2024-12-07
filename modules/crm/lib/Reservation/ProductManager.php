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
	 * @param array $entityProducts in format `[basketXmlId => entityProductRow]`
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

		foreach ($entityProducts as $basketXmlId => $entityProduct)
		{
			$entityProducts[$basketXmlId]['PRODUCT'] = $this->convertToSaleBasketFormat($entityProduct['PRODUCT']);
		}

		if ($entityProducts)
		{
			$defaultStoreId = Catalog\StoreTable::getDefaultStoreId();
			$currency = $this->getOrder()->getCurrency();

			/** @var Crm\Order\BasketItem[] $basketItems */
			$basketItems = [];
			$foundProducts = [];

			foreach ($entityProducts as $product)
			{
				$quantity = (float)$product['QUANTITY'];
				if ($quantity <= 0.0)
				{
					continue;
				}

				$productData = $product['PRODUCT'];

				$basketItem = $this->getBasketItemByEntityProduct($productData, $foundProducts, true);
				if (!$basketItem)
				{
					$basketItem = $basket->createItem('catalog', $productData['PRODUCT_ID']);
					$basketItem->setField('XML_ID', $productData['XML_ID']);
					$foundProducts[] = $basketItem->getBasketCode();
				}

				$basketItemFields = [
					'LID' => SITE_ID,
					'NAME' => $productData['NAME'],
					'QUANTITY' => $quantity,
					'CURRENCY' => $currency,
					'BASE_PRICE' => $productData['BASE_PRICE'],
					'PRICE' => $productData['PRICE'],
					'CUSTOM_PRICE' => 'Y',
					'MEASURE_CODE' => $productData['MEASURE_CODE'],
					'MEASURE_NAME' => $productData['MEASURE_NAME'],
					'PRODUCT_PROVIDER_CLASS' => '\\' . Catalog\Product\CatalogProvider::class,
					'TYPE' => $productData['TYPE'] ?? null,
					'VAT_RATE' => $productData['VAT_RATE'] ?? null,
					'VAT_INCLUDED' => $productData['VAT_INCLUDED'] ?? 'N',
				];

				$setFieldsResult = $basketItem->setFields($basketItemFields);
				if ($setFieldsResult->isSuccess())
				{
					$basketItems[] = $basketItem;
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
				// it can be empty if there are no reserves for the product
				$storeList = $entityProducts[$basketItem->getField('XML_ID')]['STORE_LIST'] ?? [];
				if (empty($storeList))
				{
					$storeList = [
						$defaultStoreId => $basketItem->getQuantity(),
					];
				}

				$shipmentItemQuantity = array_sum($storeList);
				if ($shipmentItemQuantity <= 0.0)
				{
					continue;
				}

				/** @var Crm\Order\ShipmentItem $shipmentItem */
				$shipmentItem = $newShipmentItemCollection->createItem($basketItem);
				$setQuantityResult = $shipmentItem->setQuantity($shipmentItemQuantity);
				if (!$setQuantityResult->isSuccess())
				{
					$result->addErrors($setQuantityResult->getErrors());
					continue;
				}

				if (!$basketItem->isReservableItem())
				{
					continue;
				}

				/** @var Crm\Order\ShipmentItemStoreCollection $shipmentItemStoreCollection */
				$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
				if (!$shipmentItemStoreCollection)
				{
					$shipmentItem->delete();

					continue;
				}

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

		if ($result->isSuccess() && $newShipmentItemCollection->isEmpty())
		{
			$newShipment->delete();
		}

		return $result;
	}
}

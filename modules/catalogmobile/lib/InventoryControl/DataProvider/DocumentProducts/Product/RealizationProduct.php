<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Sale\ShipmentItem;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Crm\Order\ShipmentItemStore;
use Bitrix\Sale\ReserveQuantity;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Manager;
use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Order\ProductManager;
use Bitrix\Crm\Order\ProductManager\EntityProductConverterWithReserve;
use Bitrix\Crm\Order\PayableBasketItem;
use Bitrix\Crm\Order\BasketItem;
use Bitrix\Sale;
use Bitrix\Sale\Reservation\BasketReservationService;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Sale\Payment;

Loader::includeModule('catalog');

final class RealizationProduct extends BaseProduct
{
	public static function load(?int $documentId = null, array $context = []): array
	{
		if (!Loader::includeModule('sale'))
		{
			return [];
		}

		if ($documentId === null)
		{
			return self::enrichEntityProducts($context);
		}

		$shipment = ShipmentRepository::getInstance()->getById($documentId);
		if (!$shipment)
		{
			return [];
		}

		$records = [];

		$defaultStore = StoreTable::getDefaultStoreId();

		$productIds = [];
		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$productIds[] = $basketItem->getProductId();
		}
		$productStoreInfo = self::getProductStoreInfo($productIds);

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();

			$documentProduct = [
				'ID' => $basketItem->getId(),
				'NAME' => $basketItem->getField('NAME'),
				'STORE_FROM' => 0,
				'ELEMENT_ID' => $basketItem->getProductId(),
				'PRICE_WITH_VAT' => $basketItem->getPriceWithVat(),
				'BASE_PRICE' => $basketItem->getPrice(),
				'PRICE' => $basketItem->getPrice(),
				'BASE_PRICE_EXTRA' => '',
				'BASE_PRICE_EXTRA_RATE' => '',
				'BASKET_ID' => $basketItem->getId(),
				'BASKET_CODE' => $basketItem->getBasketCode(),
				'AMOUNT' => 0,
				'BARCODE' => '',
				'CURRENCY' => $basketItem->getCurrency(),
				'VAT_RATE' => $basketItem->getVatRate(),
				'VAT_INCLUDED' => $basketItem->getField('VAT_INCLUDED'),
				'VAT' => $basketItem->getVatUnit(false),
			];

			$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
			if ($shipmentItemStoreCollection && !$shipmentItemStoreCollection->isEmpty())
			{
				/** @var ShipmentItemStore $shipmentItemStore */
				foreach ($shipmentItemStoreCollection as $shipmentItemStore)
				{
					if ($basketItem->isReservableItem())
					{
						$documentProduct['STORE_FROM'] = $shipmentItemStore->getStoreId();
					}

					$documentProduct['AMOUNT'] = $shipmentItemStore->getQuantity();
					$documentProduct['BARCODE'] = $shipmentItemStore->getBarcode();
				}
			}
			else
			{
				$storeId = $defaultStore;

				$reserveQuantityCollection = $basketItem->getReserveQuantityCollection();
				if ($reserveQuantityCollection && $reserveQuantityCollection->count() === 1)
				{
					/** @var ReserveQuantity $reserveQuantity */
					$reserveQuantity = $reserveQuantityCollection->current();
					$storeId = $reserveQuantity->getStoreId();
				}

				if ($basketItem->isReservableItem())
				{
					$documentProduct['STORE_FROM'] = $storeId;
				}

				$documentProduct['AMOUNT'] = $shipmentItem->getQuantity();
			}

			$hasStoreFromAccess = $documentProduct['STORE_FROM'] ? self::hasStoreAccess((int)$documentProduct['STORE_FROM']) : true;

			$storeFromAvailableAmount = 0;
			if ($documentProduct['ELEMENT_ID'])
			{
				$storeFromAvailableAmount = self::getAvailableProductAmountOnStore(
					$productStoreInfo,
					(int)$documentProduct['ELEMENT_ID'],
					(int)$documentProduct['STORE_FROM']
				);
			}
			$storeFromAmount =
				(float)$productStoreInfo[(int)$documentProduct['ELEMENT_ID']][(int)$documentProduct['STORE_FROM']]['AMOUNT']
				?? 0
			;

			$records[] = DocumentProductRecord::make([
				'id' => $documentProduct['ID'],
				'documentId' => $documentId,
				'productId' => (int)$documentProduct['ELEMENT_ID'],
				'storeFromId' => $hasStoreFromAccess ? (int)$documentProduct['STORE_FROM'] : 0,
				'hasStoreFromAccess' => $hasStoreFromAccess,
				'name' => $documentProduct['NAME'],
				'storeFromAvailableAmount' => $storeFromAvailableAmount,
				'storeFromAmount' => $storeFromAmount,
				'amount' => (float)$documentProduct['AMOUNT'],
				'basketCode' => $documentProduct['BASKET_CODE'],
				'price' => [
					'sell' => [
						'basePrice' => (float)$documentProduct['BASE_PRICE'],
						'amount' => (float)$documentProduct['PRICE'],
						'currency' => $documentProduct['CURRENCY'],
					],
					'vat' => [
						'priceWithVat' => (float)$documentProduct['PRICE_WITH_VAT'],
						'vatRate' => $documentProduct['VAT_RATE'],
						'vatIncluded' => $documentProduct['VAT_INCLUDED'],
						'vatValue' => $documentProduct['VAT'],
					],
				],
				'barcode' => $documentProduct['BARCODE'],
				'oldBarcode' => $documentProduct['BARCODE'],
			]);
		}

		return self::enrich($records, [
			new CompleteSku(),
			new CompleteSections(),
			new CompleteStores(),
		]);
	}

	private static function enrichEntityProducts(array $context = []): array
	{
		$records = [];
		$products = self::getEntityProducts($context);
		foreach ($products as $product)
		{
			$records[] = DocumentProductRecord::make($product);
		}

		return self::enrich($records, [
			new CompleteSku(),
			new CompleteSections(),
			new CompleteStores(),
			new CompleteBarcodes(),
		]);
	}

	public static function getEntityProducts(array $context = []): array
	{
		$payment = null;
		$products = [];

		if ($context['paymentId'])
		{
			$payment = PaymentRepository::getInstance()->getById($context['paymentId']);
			if ($payment)
			{
				$order = $payment->getOrder();
			}
			else
			{
				throw new \DomainException('Payment document not found!');
			}
		}
		elseif ($context['orderId'] && $context['orderId'] > 0)
		{
			$order = Order::load($context['orderId']);
			if (!$order)
			{
				throw new \DomainException('Order not found!');
			}
		}
		else
		{
			$order = Manager::createEmptyOrder(SITE_ID);
		}

		$bindingEntity = self::getOwnerEntity($order, $context);
		if (!$bindingEntity)
		{
			return [];
		}

		$ownerTypeId = $bindingEntity->getEntityTypeId();
		$ownerId = $bindingEntity->getId();

		$orderIds = OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);

		if (
			!\CCrmSaleHelper::isWithOrdersMode()
			|| count($orderIds) === 0
		)
		{
			$productManager = new ProductManager($ownerTypeId, $ownerId);
			$productManager->setProductConverter(
				new EntityProductConverterWithReserve()
			);

			if ($orderIds)
			{
				$order = Order::load(max($orderIds));
				if ($order)
				{
					$productManager->setOrder($order);
				}
			}

			$defaultStoreId = StoreTable::getDefaultStoreId();

			$basketIdsFilter = self::getEntityProductsBasketIdFilter($payment);
			$deliverableProducts = $productManager->getRealizationableItems();
			$productStoreInfo = self::getProductStoreInfo(array_column($deliverableProducts, 'PRODUCT_ID'));
			foreach ($deliverableProducts as $deliverableProduct)
			{
				if (
					!empty($basketIdsFilter)
					&& !in_array($deliverableProduct['BASKET_CODE'], $basketIdsFilter, true)
				)
				{
					continue;
				}

				$reserve = $deliverableProduct['RESERVE'] ? current($deliverableProduct['RESERVE']) : [];
				if (empty($reserve['STORE_ID']))
				{
					$currentProductStoreInfo = $productStoreInfo[$deliverableProduct['PRODUCT_ID']] ?? [];
					$filledStores = array_filter($currentProductStoreInfo, static function($element) {
						return (int)$element['AMOUNT'] > 0;
					});
					$deliverableProduct['STORE_ID'] =
						$filledStores
							? (int)current($filledStores)['STORE_ID']
							: $defaultStoreId
					;
				}
				else
				{
					$deliverableProduct['STORE_ID'] = (int)$reserve['STORE_ID'];
				}

				$quantity = self::getEntityProductQuantity($deliverableProduct);

				if ($quantity <= 0)
				{
					continue;
				}

				$hasStoreFromAccess =
					!$deliverableProduct['STORE_FROM']
					|| self::hasStoreAccess((int)$deliverableProduct['STORE_FROM'])
				;

				$storeFromAvailableAmount = 0;
				if ($deliverableProduct['PRODUCT_ID'])
				{
					$storeFromAvailableAmount = self::getAvailableProductAmountOnStore(
						$productStoreInfo,
						$deliverableProduct['PRODUCT_ID'],
						$deliverableProduct['STORE_ID']
					);
				}
				$storeFromAmount =
					(float)$productStoreInfo[$deliverableProduct['PRODUCT_ID']][$deliverableProduct['STORE_ID']]['AMOUNT']
					?? 0
				;

				$priceWithVat = (float)$deliverableProduct['PRICE'];
				$vatRate = null;
				$vatValue = 0;

				if ($deliverableProduct['VAT_RATE'] !== null)
				{
					$isVatInPrice = $deliverableProduct['VAT_INCLUDED'] === 'Y';
					$vatRate = $deliverableProduct['VAT_RATE'];
					$vatCalculator = new Sale\Tax\VatCalculator($vatRate);

					$priceWithVat = $isVatInPrice
						? $priceWithVat
						: $vatCalculator->accrue($deliverableProduct['PRICE']);

					$vatValue = $vatCalculator->calc(
						$deliverableProduct['PRICE'],
						$isVatInPrice
					);
				}

				$products[] = [
					'id' => uniqid('bx_', true),
					'documentId' => null,
					'productId' => (int)$deliverableProduct['PRODUCT_ID'],
					'storeFromId' => $hasStoreFromAccess ? $deliverableProduct['STORE_ID'] : 0,
					'storeFromAvailableAmount' => $storeFromAvailableAmount,
					'storeFromAmount' => $storeFromAmount,
					'hasStoreFromAccess' => $hasStoreFromAccess,
					'name' => $deliverableProduct['NAME'],
					'amount' => $quantity,
					'basketCode' => $deliverableProduct['BASKET_CODE'],
					'price' => [
						'sell' => [
							'basePrice' => $deliverableProduct['BASE_PRICE'],
							'amount' => $deliverableProduct['PRICE'],
							'currency' => $order->getCurrency(),
						],
						'vat' => [
							'priceWithVat' => $priceWithVat,
							'vatRate' => $vatRate,
							'vatIncluded' => $deliverableProduct['VAT_INCLUDED'],
							'vatValue' => $vatValue,
						],
					],
				];
			}
		}

		return $products;
	}

	private static function getEntityProductsBasketIdFilter(Payment $payment = null): array
	{
		if (!$payment)
		{
			return [];
		}

		$result = [];

		/** @var PayableBasketItem $basketItem */
		foreach ($payment->getPayableItemCollection()->getBasketItems() as $payableItem)
		{
			/** @var BasketItem $basketItem */
			$basketItem = $payableItem->getEntityObject();

			$result[] = $basketItem->getId();
		}

		return $result;
	}

	private static function getEntityProductQuantity(array $product): float
	{
		$quantity = $product['QUANTITY'];

		if ($product['TYPE'] !== Sale\BasketItem::TYPE_SERVICE)
		{
			if ((int)$product['BASKET_CODE'] > 0)
			{
				/** @var BasketReservationService $basketReservation */
				$basketReservation = ServiceLocator::getInstance()->get('sale.basketReservation');

				$availableQuantity = $basketReservation->getAvailableCountForBasketItem(
					(int)$product['BASKET_CODE'],
					$product['STORE_ID']
				);

				$quantity = min($quantity, $availableQuantity);
			}
			else
			{
				$storeQuantityRow = StoreProductTable::getRow([
					'select' => [
						'AMOUNT',
						'QUANTITY_RESERVED',
					],
					'filter' => [
						'=STORE_ID' => $product['STORE_ID'],
						'=PRODUCT_ID' => $product['PRODUCT_ID'],
					],
				]);
				if ($storeQuantityRow)
				{
					$availableQuantity = $storeQuantityRow['AMOUNT'] - $storeQuantityRow['QUANTITY_RESERVED'];
					$quantity = min($quantity, $availableQuantity);
				}
			}
		}

		return $quantity;
	}

	private static function getOwnerEntity(Order $order, array $context = []): ?Item
	{
		static $item = null;

		if ($item)
		{
			return $item;
		}

		$ownerTypeId = $context['ownerTypeId'] ?? null;
		$ownerId = $context['ownerId'] ?? null;

		$isOwnerContext = $ownerTypeId && $ownerId;
		if (!$isOwnerContext)
		{
			$entityBinding = $order->getEntityBinding();
			if ($entityBinding)
			{
				$ownerTypeId = $entityBinding->getOwnerTypeId();
				$ownerId = $entityBinding->getOwnerId();
			}
		}

		if ($ownerTypeId && $ownerId)
		{
			$factory = Container::getInstance()->getFactory($ownerTypeId);
			if ($factory)
			{
				$item = $factory->getItem($ownerId);
				if ($item)
				{
					return $item;
				}
			}
		}

		return null;
	}
}

<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Crm\Order\BasketItem;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\ProductManager;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Helpers\Order\Builder\BuildingException;
use Bitrix\Sale\ReserveQuantity;
use Bitrix\Catalog\Product;
use Bitrix\Catalog\v2\Helpers\PropertyValue;
use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\Order\Builder\Factory;
use Bitrix\Main\Loader;
use Bitrix\Sale\Helpers\Order\Builder\Converter\CatalogJSProductForm;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Crm\ProductRow;

/**
 * @deprecated 23.0.0
 * @see \Bitrix\Crm\Order\OrderDealSynchronizer
 */
class OrderSynchronizer
{
	/** @var int */
	private $dealId;

	/** @var ProductRowCollection $dealProducts */
	private $dealProducts;

	/** @var Order|null */
	private $order;

	/** @var array|false|null $dealFields */
	private $dealFields;

	/**
	 * @param int $dealId
	 * @param ProductRowCollection $dealProducts
	 * @param int|null $orderId
	 */
	public function __construct(int $dealId, ProductRowCollection $dealProducts, int $orderId)
	{
		Loader::requireModule('catalog');
		Loader::requireModule('sale');

		$this->dealId = $dealId;
		$this->dealFields = \CCrmDeal::GetByID($dealId, false);

		$this->dealProducts = $dealProducts;

		if ($orderId)
		{
			$this->order = Order::load($orderId);
		}
	}

	/**
	 * @return bool
	 */
	private function shouldSynchronize(): bool
	{
		if ($this->order)
		{
			return true;
		}

		$defaultStoreId = StoreTable::getDefaultStoreId();
		/** @var ProductRow $dealProduct */
		foreach ($this->dealProducts as $dealProduct)
		{
			$productRowReservation = $dealProduct->getProductRowReservation();
			if ($productRowReservation)
			{
				$storeId = $defaultStoreId;
				if ($productRowReservation->getStoreId() > 0)
				{
					$storeId = $productRowReservation->getStoreId();
				}

				$reserveQuantity = $productRowReservation->getReserveQuantity() ?: 0;

				if ($storeId !== $defaultStoreId || $reserveQuantity > 0)
				{
					return true;
				}
			}
		}

		return false;
	}

	public function synchronize(): void
	{
		if (!$this->shouldSynchronize())
		{
			return;
		}

		$orderBuilder = Factory::createBuilderForReservation();

		$wasAutomaticReservationEnabled = self::isAutomaticReservationEnabled();
		if ($wasAutomaticReservationEnabled)
		{
			self::disableAutomaticReservation();
		}

		try
		{
			$orderBuilder->build(
				$this->makeFormDataForOrderBuilder(
					$this->getMergedProducts()
				)
			);
			/** @var Order $order */
			$order = $orderBuilder->getOrder();
		}
		catch (BuildingException $exception)
		{
			$order = null;
		}

		if (!$order)
		{
			return;
		}

		self::disableContactAutoCreationModeByOrder($order);

		if (isset($this->dealFields['CURRENCY_ID']) && $order->getCurrency() !== $this->dealFields['CURRENCY_ID'])
		{
			$order->changeCurrency($this->dealFields['CURRENCY_ID']);
		}

		$order->save();

		if ($wasAutomaticReservationEnabled)
		{
			self::enableAutomaticReservation();
		}
	}

	/**
	 * @return array
	 */
	private function getMergedProducts(): array
	{
		/**
		 * Order Products
		 */
		$orderProducts = $this->getOrderProducts();

		/**
		 * Deal Products
		 */
		$dealProducts = [];

		/** @var ProductRow $dealProduct */
		foreach ($this->dealProducts as $dealProduct)
		{
			$dealProductFields = $dealProduct->toArray();
			$productReservation = $dealProduct->getProductRowReservation();
			if ($productReservation)
			{
				$dealProductFields['RESERVE_ID'] = $productReservation->getReserveId();
				$dealProductFields['RESERVE_QUANTITY'] = $productReservation->getReserveQuantity();
				$dealProductFields['STORE_ID'] = $productReservation->getStoreId();
				$dealProductFields['DATE_RESERVE_END'] = (string)$productReservation->getDateReserveEnd();
			}

			$dealProducts[] = (new ProductManager\EntityProductConverterWithReserve)->convertToSaleBasketFormat($dealProductFields);
		}

		/**
		 * Merge
		 */
		$result = [];
		$counter = 0;
		$isNewOrder = empty($orderProducts);

		$foundProducts = [];
		foreach ($dealProducts as $product)
		{
			if ($isNewOrder)
			{
				$product['BASKET_CODE'] = 'n' . (++$counter);
				$product['PROPS'] = self::getBasketItemProps($product['PRODUCT_ID']);
			}
			else
			{
				$index = self::searchProduct($product, $orderProducts, $foundProducts);
				if ($index === false)
				{
					$product['BASKET_CODE'] = 'n' . (++$counter);
					$product['PROPS'] = self::getBasketItemProps($product['PRODUCT_ID']);
				}
				else
				{
					$product['BASKET_CODE'] = $orderProducts[$index]['BASKET_CODE'];
					$foundProducts[] = $product['BASKET_CODE'];

					if ($product['QUANTITY'] < $orderProducts[$index]['QUANTITY'])
					{
						$product['QUANTITY'] = $orderProducts[$index]['QUANTITY'];
					}
				}
			}

			$result[] = $product;
		}

		return $result;
	}

	/**
	 * @param Order $order
	 */
	private static function disableContactAutoCreationModeByOrder(Order $order): void
	{
		$contactCompanyCollection = $order->getContactCompanyCollection();
		if ($contactCompanyCollection)
		{
			$contactCompanyCollection->disableAutoCreationMode();
		}
	}

	/**
	 * @return bool
	 */
	private static function isAutomaticReservationEnabled(): bool
	{
		return Configuration::isEnableAutomaticReservation();
	}

	private static function disableAutomaticReservation(): void
	{
		Configuration::disableAutomaticReservation();
	}

	private static function enableAutomaticReservation(): void
	{
		Configuration::enableAutomaticReservation();
	}

	/**
	 * @param array $products
	 * @return array
	 */
	private function prepareProductsForBuilder(array $products): array
	{
		$result = [];

		foreach ($products as $product)
		{
			if ($product['MODULE'] === 'catalog')
			{
				$product['PRODUCT_PROVIDER_CLASS'] = Product\Basket::getDefaultProviderName();
			}

			$product['MANUALLY_EDITED'] = 'Y';
			$product['FIELDS_VALUES'] = Json::encode($product);
			$result[$product['BASKET_CODE']] = $product;
		}

		return $result;
	}

	/**
	 * @param array $products
	 * @return array
	 */
	private function makeFormDataForOrderBuilder(array $products): array
	{
		$result = [
			'OWNER_ID' => $this->dealId,
			'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
			'RESPONSIBLE_ID' => $this->dealFields['ASSIGNED_BY_ID'],
			'CURRENCY' => $this->dealFields['CURRENCY_ID'],
		];

		if ($this->order)
		{
			$result['ID'] = $this->order->getId();

			if ($this->order->getCurrency())
			{
				$result['CURRENCY'] = $this->order->getCurrency();
			}

			if ($this->order->getUserId())
			{
				$result['USER_ID'] = $this->order->getUserId();
			}
		}

		$result['PRODUCT'] = $this->prepareProductsForBuilder($products);

		/**
		 * Client Info
		 */
		if ($this->order && !$this->order->getContactCompanyCollection()->isEmpty())
		{
			$result['CLIENT'] = ClientInfo::createFromOwner(\CCrmOwnerType::Order, $this->order->getId())->toArray(false);
		}
		else
		{
			$result['CLIENT'] = ClientInfo::createFromOwner(\CCrmOwnerType::Deal, $this->dealId)->toArray(false);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getOrderProducts(): array
	{
		if (!$this->order)
		{
			return [];
		}

		$result = [];

		$basket = $this->order->getBasket();

		/** @var BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$resultItem = [
				'BASKET_CODE' => $basketItem->getBasketCode(),
				'MODULE' => $basketItem->getField('MODULE'),
				'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
				'OFFER_ID' => $basketItem->getField('PRODUCT_ID'),
				'QUANTITY' => (float)$basketItem->getField('QUANTITY'),
			];

			$resultItem['RESERVE'] = [];

			$reserveCollection = $basketItem->getReserveQuantityCollection();
			if ($reserveCollection)
			{
				/** @var ReserveQuantity $reserveItem */
				foreach ($reserveCollection as $reserveItem)
				{
					$resultItem['RESERVE'][$reserveItem->getId()] = [
						'QUANTITY' => $reserveItem->getQuantity(),
						'STORE_ID' => $reserveItem->getStoreId(),
						'DATE_RESERVE_END' => $basketItem->getField('DATE_RESERVE_END'),
						'RESERVED_BY' => $basketItem->getField('RESERVED_BY'),
					];
				}
			}

			$result[] = $resultItem;
		}

		return $result;
	}

	/**
	 * @param int $productId
	 * @return array
	 */
	private static function getBasketItemProps(int $productId): array
	{
		$allowedPropertyCodes = CatalogJSProductForm::getAllowedBasketProperties($productId);
		if (!$allowedPropertyCodes)
		{
			return [];
		}

		$sku = ServiceContainer::getRepositoryFacade()->loadVariation($productId);
		if (!$sku)
		{
			return [];
		}

		$result = [];

		$propertyValues = PropertyValue::getPropertyValuesBySku($sku);
		foreach ($propertyValues as $propertyValue)
		{
			if (!in_array($propertyValue['CODE'], $allowedPropertyCodes, true))
			{
				continue;
			}

			$result[] = [
				'NAME' => $propertyValue['NAME'],
				'SORT' => $propertyValue['SORT'],
				'CODE' => $propertyValue['CODE'],
				'VALUE' => PropertyValue::getPropertyDisplayValue($propertyValue),
			];
		}

		return $result;
	}

	/**
	 * @param array $searchableProduct
	 * @param array $productList
	 * @param array $foundProducts
	 *
	 * @return false|int|string
	 */
	private static function searchProduct(array $searchableProduct, array $productList, array & $foundProducts)
	{
		if ((int)$searchableProduct['PRODUCT_ID'] === 0)
		{
			return false;
		}

		foreach ($productList as $index => $item)
		{
			if (
				(int)$searchableProduct['PRODUCT_ID'] === (int)$item['PRODUCT_ID']
				&& $searchableProduct['MODULE'] === $item['MODULE']
				&& !in_array($item['BASKET_CODE'], $foundProducts, true)
			)
			{
				if (!empty($searchableProduct['RESERVE']) && !empty($item['RESERVE']))
				{
					$searchableProductReserveIds = array_keys($searchableProduct['RESERVE']);
					$itemReserveIds = array_keys($item['RESERVE']);

					if (!array_intersect($searchableProductReserveIds, $itemReserveIds))
					{
						continue;
					}
				}

				return $index;
			}
		}

		return false;
	}
}

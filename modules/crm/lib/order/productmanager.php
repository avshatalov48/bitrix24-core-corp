<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

class ProductManager
{
	/** @var Order $order */
	private $order;

	/** @var int $dealId */
	private $dealId;

	public function __construct(int $dealId)
	{
		$this->dealId = $dealId;
	}

	/**
	 * @param Order $order
	 * @return $this
	 */
	public function setOrder(Order $order) : self
	{
		$this->order = $order;

		return $this;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	public function getDeliverableItems() : array
	{
		$orderProducts = $this->getUnShippableProductList();
		$dealProducts = $this->getDealProductList();

		return $this->mergeProducts($orderProducts, $dealProducts);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 */
	public function getPayableItems() : array
	{
		$unPayableProductList = $this->getUnPayableProductList();
		$dealProducts = $this->getDealProductList();

		return $this->mergeProducts($unPayableProductList, $dealProducts);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function getUnPayableProductList() : array
	{
		$products = [];

		if ($this->order)
		{
			$basket = $this->order->getBasket();

			/** @var BasketItem $basketItem */
			foreach ($basket as $basketItem)
			{
				$diff = $basketItem->getQuantity() - $this->getPayableQuantityByBasketItem($basketItem);
				if ($diff <= 1e-10)
				{
					continue;
				}

				$item = $this->extractDataFromBasketItem($basketItem);
				$item['QUANTITY'] = $diff;

				$products[] = $item;
			}
		}

		return $products;
	}

	protected function getPayableQuantityByBasketItem(BasketItem $item) : float
	{
		$quantity = 0;

		if ($this->order)
		{
			/** @var Payment $payment */
			foreach ($this->order->getPaymentCollection() as $payment)
			{
				/** @var Sale\PayableBasketItem $payable */
				foreach ($payment->getPayableItemCollection()->getBasketItems() as $payable)
				{
					$basketItem = $payable->getEntityObject();
					if ($basketItem->getBasketCode() === $item->getBasketCode())
					{
						$quantity += $payable->getQuantity();
					}
				}
			}
		}

		return $quantity;
	}

	protected function extractDataFromBasketItem(BasketItem $basketItem) : array
	{
		return [
			'BASKET_CODE' => $basketItem->getBasketCode(),
			'MODULE' => $basketItem->getField('MODULE'),
			'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
			'QUANTITY' => (float)$basketItem->getField('QUANTITY'),
		];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	protected function getUnShippableProductList() : array
	{
		$products = [];

		if ($this->order)
		{
			$shipment = $this->order->getShipmentCollection()->getSystemShipment();

			/** @var ShipmentItem $shipmentItem */
			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
			{
				/** @var BasketItem $basketItem */
				$basketItem = $shipmentItem->getBasketItem();

				$item = $this->extractDataFromBasketItem($basketItem);
				$item['QUANTITY'] = $shipmentItem->getQuantity();

				$products[] = $item;
			}
		}

		return $products;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 */
	protected function getDealProductList() : array
	{
		$products = [];

		if ($this->dealId === 0)
		{
			return $products;
		}

		if (Main\Loader::includeModule('crm'))
		{
			$dealProductList = \CCrmDeal::LoadProductRows($this->dealId);
			foreach ($dealProductList as $dealProduct)
			{
				$products[] = $this->convertToSaleBasketFormat($dealProduct);
			}
		}

		return $products;
	}

	/**
	 * @param $product
	 * @return array
	 */
	private function convertToSaleBasketFormat($product) : array
	{
		return [
			'NAME' => $product['PRODUCT_NAME'],
			'MODULE' => $product['PRODUCT_ID'] ? 'catalog' : '',
			'PRODUCT_ID' => $product['PRODUCT_ID'],
			'QUANTITY' => $product['QUANTITY'],
			'BASE_PRICE' => $product['PRICE_NETTO'],
			'PRICE' => $product['PRICE'],
			'PRICE_EXCLUSIVE' => $product['PRICE_EXCLUSIVE'],
			'CUSTOM_PRICE' => 'Y',
			'DISCOUNT_SUM' => $product['DISCOUNT_SUM'],
			'DISCOUNT_RATE' => $product['DISCOUNT_RATE'],
			'DISCOUNT_TYPE_ID' => $product['DISCOUNT_TYPE_ID'],
			'MEASURE_CODE' => $product['MEASURE_CODE'],
			'MEASURE_NAME' => $product['MEASURE_NAME'],
			'TAX_RATE' => $product['TAX_RATE'],
			'TAX_INCLUDED' => $product['TAX_INCLUDED'],
		];
	}

	/**
	 * @param array $product
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	protected function needAddToBasket(array $product) : bool
	{
		$item = $this->getBasketItemByDealProduct($product);
		if ($item)
		{
			return $item->getQuantity() < (float)$product['QUANTITY'];
		}

		return true;
	}

	/**
	 * @param array $product
	 * @return Sale\BasketItem|null
	 */
	protected function getBasketItemByDealProduct(array $product) :? Sale\BasketItem
	{
		if (!$this->order)
		{
			return null;
		}

		/** @var Sale\BasketItem $basketItem */
		foreach ($this->order->getBasket() as $basketItem)
		{
			if (
				$basketItem->getProductId() === (int)$product['PRODUCT_ID']
				&& $basketItem->getField('MODULE') === $product['MODULE']
			)
			{
				return $basketItem;
			}
		}

		return null;
	}

	/**
	 * @param $orderProducts
	 * @param $dealProducts
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	private function mergeProducts($orderProducts, $dealProducts) : array
	{
		$result = [];

		$counter = 0;
		foreach ($dealProducts as $product)
		{
			$index = $this->searchProduct($product, $orderProducts);
			if ($index === false)
			{
				$basketItem = $this->getBasketItemByDealProduct($product);
				if ($basketItem)
				{
					if ($product['QUANTITY'] <= $basketItem->getQuantity())
					{
						continue;
					}

					$product['BASKET_CODE'] = $basketItem->getBasketCode();
					$product['QUANTITY'] -= $basketItem->getQuantity();
				}
				else
				{
					$product['BASKET_CODE'] = 'n'.(++$counter);
				}
			}
			else
			{
				$basketItem = $this->order->getBasket()->getItemByBasketCode($orderProducts[$index]['BASKET_CODE']);
				if (!$basketItem)
				{
					continue;
				}

				$product['BASKET_CODE'] = $basketItem->getBasketCode();

				if ($basketItem->getQuantity() !== $orderProducts[$index]['QUANTITY'])
				{
					$product['QUANTITY'] -= $orderProducts[$index]['QUANTITY'];
				}
			}

			$result[] = $product;
		}

		return $result;
	}

	protected function searchProduct(array $searchableProduct, array $productList)
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
			)
			{
				return $index;
			}
		}

		return false;
	}
}
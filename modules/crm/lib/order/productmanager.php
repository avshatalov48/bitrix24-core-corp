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

	/** @var int $ownerTypeId */
	private $ownerTypeId;

	/** @var int $ownerId */
	private $ownerId;

	/** @var ProductManager\ProductConverter */
	private $productConverter;

	/**
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 */
	public function __construct(int $ownerTypeId, int $ownerId)
	{
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;

		$this->productConverter = new ProductManager\EntityProductConverter();
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
	 * @return Order|null
	 */
	public function getOrder(): ?Order
	{
		return $this->order;
	}

	/**
	 * @param ProductManager\ProductConverter $productConverter
	 * @return void
	 */
	public function setProductConverter(ProductManager\ProductConverter $productConverter): void
	{
		$this->productConverter = $productConverter;
	}

	/**
	 * @return array
	 */
	public function getDeliverableItems() : array
	{
		$orderProducts = $this->getUnShippableProductList();
		$entityProducts = $this->getConvertedToBasketEntityProductList();

		return $this->mergeProducts($orderProducts, $entityProducts);
	}

	/**
	 * @return array
	 */
	public function getPayableItems() : array
	{
		$unPayableProductList = $this->getUnPayableProductList();
		$entityProducts = $this->getConvertedToBasketEntityProductList();

		return $this->mergeProducts($unPayableProductList, $entityProducts);
	}

	/**
	 * @return array
	 */
	protected function getProductList() : array
	{
		$products = [];

		if ($this->order)
		{
			$basket = $this->order->getBasket();

			/** @var BasketItem $basketItem */
			foreach ($basket as $basketItem)
			{
				$products[] = $this->extractDataFromBasketItem($basketItem);
			}
		}

		return $products;
	}

	/**
	 * @return array
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

	/**
	 * @param BasketItem $item
	 * @return float
	 */
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

	/**
	 * @param BasketItem $basketItem
	 * @return array
	 */
	protected function extractDataFromBasketItem(BasketItem $basketItem) : array
	{
		return [
			'BASKET_CODE' => $basketItem->getBasketCode(),
			'MODULE' => $basketItem->getField('MODULE'),
			'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
			'OFFER_ID' => $basketItem->getField('PRODUCT_ID'),
			'QUANTITY' => (float)$basketItem->getField('QUANTITY'),
		];
	}

	/**
	 * @return array
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
	 */
	public function getEntityProductList(): array
	{
		$productList = [];

		if ($this->ownerId === 0)
		{
			return $productList;
		}

		if ($this->isDeal())
		{
			$productList = $this->getDealProductList();
		}
		elseif ($this->isDynamicEntity())
		{
			$productList = $this->getDynamicEntityProductList();
		}

		return $productList;
	}

	/**
	 * @return array
	 */
	protected function getConvertedToBasketEntityProductList(): array
	{
		$products = [];

		$productList = $this->getEntityProductList();
		foreach ($productList as $product)
		{
			$products[] = $this->convertToSaleBasketFormat($product);
		}

		return $products;
	}

	/**
	 * @return array
	 */
	protected function getDealProductList(): array
	{
		return \CCrmDeal::LoadProductRows($this->ownerId);
	}

	/**
	 * @return array
	 */
	protected function getDynamicEntityProductList(): array
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$dynamicEntity = $factory->getItem($this->ownerId);
			if ($dynamicEntity)
			{
				$productsList = $dynamicEntity->getProductRows();
				if ($productsList)
				{
					return $productsList->toArray();
				}
			}
		}

		return [];
	}

	/**
	 * @param $product
	 * @return array
	 */
	protected function convertToSaleBasketFormat($product): array
	{
		return $this->productConverter->convertToSaleBasketFormat($product);
	}

	/**
	 * @param array $product
	 * @return Sale\BasketItem|null
	 */
	protected function getBasketItemByEntityProduct(array $product) :? Sale\BasketItem
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
	 */
	private function mergeProducts($orderProducts, $dealProducts) : array
	{
		$result = [];

		$counter = 0;
		foreach ($dealProducts as $product)
		{
			$index = static::searchProduct($product, $orderProducts);
			if ($index === false)
			{
				$basketItem = $this->getBasketItemByEntityProduct($product);
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

	/**
	 * @param array $searchableProduct
	 * @param array $productList
	 * @return false|int|string
	 */
	public static function searchProduct(array $searchableProduct, array $productList)
	{
		if ((int)$searchableProduct['PRODUCT_ID'] === 0)
		{
			return false;
		}

		static $foundProducts = [];

		foreach ($productList as $index => $item)
		{
			if (
				(int)$searchableProduct['PRODUCT_ID'] === (int)$item['PRODUCT_ID']
				&& $searchableProduct['MODULE'] === $item['MODULE']
				&& !in_array($item['BASKET_CODE'], $foundProducts, true)
			)
			{
				$foundProducts[] = $item['BASKET_CODE'];
				return $index;
			}
		}

		return false;
	}

	/**
	 * @param array $productList
	 * @param int $productId
	 * @return false|int|string
	 */
	private function searchProductById(array $productList, int $productId)
	{
		if ($productId === 0)
		{
			return false;
		}

		foreach ($productList as $index => $item)
		{
			if ($productId === (int)$item['PRODUCT_ID'])
			{
				return $index;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function isDeal(): bool
	{
		return $this->ownerTypeId === \CCrmOwnerType::Deal;
	}

	/**
	 * @return bool
	 */
	private function isDynamicEntity(): bool
	{
		return \CCrmOwnerType::isUseDynamicTypeBasedApproach($this->ownerTypeId);
	}

	/**
	 * @param array $products
	 */
	public function syncOrderProducts(array $products): void
	{
		if ($this->isDeal())
		{
			$this->syncOrderProductsWithDeal($products);
		}
		elseif ($this->isDynamicEntity())
		{
			$this->syncOrderProductsWithDynamicEntity($products);
		}
	}

	/**
	 * @param array $products
	 */
	protected function syncOrderProductsWithDeal(array $products): void
	{
		$result = \CCrmDeal::LoadProductRows($this->ownerId);

		$result = $this->mergeWithOrderProducts($result, $products);
		if ($result)
		{
			\CCrmDeal::SaveProductRows($this->ownerId, $result);
		}
	}

	/**
	 * @param array $products
	 */
	protected function syncOrderProductsWithDynamicEntity(array $products): void
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$dynamicEntity = $factory->getItem($this->ownerId);
			if ($dynamicEntity)
			{
				$result = $this->getDynamicEntityProductList();
				$result = $this->mergeWithOrderProducts($result, $products);

				if ($result)
				{
					$setProductResult = $dynamicEntity->setProductRowsFromArrays($result);
					if ($setProductResult->isSuccess())
					{
						$factory->getUpdateOperation($dynamicEntity)->launch();
					}
				}
			}
		}
	}

	/**
	 * @param array $entityProducts
	 * @param array $basketProducts
	 * @return array
	 */
	private function mergeWithOrderProducts(array $entityProducts, array $basketProducts): array
	{
		$resultProductList = $entityProducts;
		if (!$this->order)
		{
			return $resultProductList;
		}

		foreach ($basketProducts as $product)
		{
			$productId = $product['skuId'] ?? $product['productId'];

			if (
				!empty($product['additionalFields']['originBasketId'])
				&& $product['additionalFields']['originBasketId'] !== $product['code']
			)
			{
				$basketItem = $this->order->getBasket()->getItemByBasketCode($product['additionalFields']['originBasketId']);

				if ($basketItem)
				{
					$index = $this->searchProductById($resultProductList, $basketItem->getProductId());
					if ($index !== false)
					{
						$resultProductList[$index]['QUANTITY'] = $basketItem->getQuantity();
					}
				}
			}
			elseif (
				!empty($product['additionalFields']['originProductId'])
				&& $product['additionalFields']['originProductId'] !== $productId
			)
			{
				$index = $this->searchProductById($resultProductList, $product['additionalFields']['originProductId']);
				if ($index !== false)
				{
					$resultProductList[$index]['PRODUCT_ID'] = $productId;
					if ($resultProductList[$index]['QUANTITY'] < $product['quantity'])
					{
						$resultProductList[$index]['QUANTITY'] = $product['quantity'];
					}

					continue;
				}
			}
			else
			{
				$index = $this->searchProductById($resultProductList, $productId);
				if ($index !== false)
				{
					$basketItem = $this->order->getBasket()->getItemByXmlId($product['innerId']);
					if ($basketItem)
					{
						if ($resultProductList[$index]['QUANTITY'] < $basketItem->getQuantity())
						{
							$resultProductList[$index]['QUANTITY'] = $basketItem->getQuantity();
						}

						$resultProductList[$index]['PRICE'] = $product['price'];
						$resultProductList[$index]['PRICE_EXCLUSIVE'] = $product['priceExclusive'];
						$resultProductList[$index]['PRICE_ACCOUNT'] = $product['price'];
						$resultProductList[$index]['PRICE_NETTO'] = $product['basePrice'];
						$resultProductList[$index]['PRICE_BRUTTO'] = $product['price'];

						if (!empty($product['discount']))
						{
							$resultProductList[$index]['DISCOUNT_TYPE_ID'] =
								(int)$product['discountType'] === Crm\Discount::MONETARY
									? Crm\Discount::MONETARY
									: Crm\Discount::PERCENTAGE
							;
							$resultProductList[$index]['DISCOUNT_RATE'] = $product['discountRate'];
							$resultProductList[$index]['DISCOUNT_SUM'] = $product['discount'];
						}

						continue;
					}
				}
			}

			$item = [
				'PRODUCT_ID' => $productId,
				'PRODUCT_NAME' => $product['name'],
				'PRICE' => $product['price'],
				'PRICE_ACCOUNT' => $product['price'],
				'PRICE_EXCLUSIVE' => $product['priceExclusive'],
				'PRICE_NETTO' => $product['basePrice'],
				'PRICE_BRUTTO' => $product['price'],
				'QUANTITY' => $product['quantity'],
				'MEASURE_CODE' => $product['measureCode'],
				'MEASURE_NAME' => $product['measureName'],
				'TAX_RATE' => $product['taxRate'],
				'TAX_INCLUDED' => $product['taxIncluded'],
			];

			if (!empty($product['discount']))
			{
				$item['DISCOUNT_TYPE_ID'] =
					(int)$product['discountType'] === Crm\Discount::MONETARY
						? Crm\Discount::MONETARY
						: Crm\Discount::PERCENTAGE
				;
				$item['DISCOUNT_RATE'] = $product['discountRate'];
				$item['DISCOUNT_SUM'] = $product['discount'];
			}

			$resultProductList[] = $item;
		}

		return $resultProductList;
	}
}

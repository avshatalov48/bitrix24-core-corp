<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

class ProductManager
{
	use Crm\Order\ProductManager\ProductFinder;

	/** @var Order $order */
	private $order;

	/** @var int $ownerTypeId */
	private $ownerTypeId;

	/** @var int $ownerId */
	private $ownerId;

	/** @var ProductManager\ProductConverter $productConverter */
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
	public function setOrder(Order $order): self
	{
		$this->order = $order;

		return $this;
	}

	/**
	 * @return Order|null
	 */
	protected function getOrder(): ?Order
	{
		return $this->order;
	}

	protected function getOrderProducts(): array
	{
		$result = [];

		if (!$this->getOrder())
		{
			return $result;
		}

		/** @var Sale\BasketItem $basketItem */
		foreach ($this->order->getBasket() as $basketItem)
		{
			$item = $basketItem->toArray();
			$item['BASKET_CODE'] = $basketItem->getBasketCode();

			$result[] = $item;
		}

		return $result;
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
	public function getDeliverableItems(): array
	{
		$orderProducts = $this->getUnShippableProductList();
		$entityProducts = $this->getConvertedToBasketEntityProductList();

		return
			(new ProductManager\MergeStrategy\Selling($this->getOrder()))
				->mergeProducts($orderProducts, $entityProducts)
			;
	}

	/**
	 * @return array
	 */
	public function getPayableItems(): array
	{
		$unPayableProductList = $this->getUnPayableProductList();
		$entityProducts = $this->getConvertedToBasketEntityProductList();

		return
			(new ProductManager\MergeStrategy\Selling($this->getOrder()))
				->mergeProducts($unPayableProductList, $entityProducts)
			;
	}

	/**
	 * @return array
	 */
	public function getRealizationableItems(): array
	{
		$orderProducts = $this->getShippableProductList();
		$entityProducts = $this->getConvertedToBasketEntityProductList();

		return
			(new ProductManager\MergeStrategy\Realization($this->getOrder()))
				->mergeProducts($orderProducts, $entityProducts)
			;
	}

	/**
	 * @return array
	 */
	protected function getUnPayableProductList(): array
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
	protected function getPayableQuantityByBasketItem(BasketItem $item): float
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
	protected function extractDataFromBasketItem(BasketItem $basketItem): array
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
	protected function getUnShippableProductList(): array
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
	protected function getShippableProductList(): array
	{
		$products = [];

		if ($this->order)
		{
			$shipmentCollection = $this->order->getShipmentCollection()->getNotSystemItems();
			foreach ($shipmentCollection as $shipment)
			{
				/** @var ShipmentItem $shipmentItem */
				foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
				{
					/** @var BasketItem $basketItem */
					$basketItem = $shipmentItem->getBasketItem();

					if (isset($products[$basketItem->getId()]))
					{
						$products[$basketItem->getId()]['QUANTITY'] += $shipmentItem->getQuantity();
					}
					else
					{
						$item = $this->extractDataFromBasketItem($basketItem);
						$item['QUANTITY'] = $shipmentItem->getQuantity();

						$products[$basketItem->getId()] = $item;
					}
				}
			}
		}

		return array_values($products);
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

	private function getOrderProductByBasketCode(array $productList, string $code)
	{
		$product = array_filter($productList, static function ($product) use ($code) {
			return (string)$product['BASKET_CODE'] === $code;
		});

		return current($product) ?? null;
	}

	private function getOrderProductByXmlId(array $productList, string $xmlId)
	{
		$product = array_filter($productList, static function ($product) use ($xmlId) {
			return $product['XML_ID'] === $xmlId;
		});

		return current($product) ?? null;
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
			\CCrmDeal::SaveProductRows($this->ownerId, $result, false);
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
	protected function mergeWithOrderProducts(array $entityProducts, array $basketProducts): array
	{
		$resultProductList = $entityProducts;

		$orderProducts = $this->getOrderProducts();
		if (empty($orderProducts))
		{
			return $resultProductList;
		}

		$usedIndexes = [];
		foreach ($basketProducts as $product)
		{
			$productId = $product['skuId'] ?? $product['productId'];

			if (
				!empty($product['additionalFields']['originBasketId'])
				&& $product['additionalFields']['originBasketId'] !== $product['code']
			)
			{
				$basketProduct = $this->getOrderProductByBasketCode($orderProducts, $product['additionalFields']['originBasketId']);
				if ($basketProduct)
				{
					$index = $this->searchProductById($resultProductList, $basketProduct['PRODUCT_ID'], $usedIndexes);
					if ($index !== false)
					{
						$resultProductList[$index]['QUANTITY'] = $basketProduct['QUANTITY'];
					}
				}
			}
			elseif (
				!empty($product['additionalFields']['originProductId'])
				&& $product['additionalFields']['originProductId'] !== $productId
			)
			{
				$index = $this->searchProductById($resultProductList, $product['additionalFields']['originProductId'], $usedIndexes);
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
				$index = $this->searchProductById($resultProductList, $productId, $usedIndexes);
				if ($index !== false)
				{
					$basketProduct = $this->getOrderProductByXmlId($orderProducts, $product['innerId']);
					if ($basketProduct)
					{
						if ($resultProductList[$index]['QUANTITY'] < $basketProduct['QUANTITY'])
						{
							$resultProductList[$index]['QUANTITY'] = $basketProduct['QUANTITY'];
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

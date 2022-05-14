<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;
use Bitrix\Catalog;

final class Manager
{
	private const CREATE_ORDER_ERROR_CODE = 'CREATE_ORDER';

	/** @var Entity\Base */
	private $entity;

	/** @var array $entityProducts */
	private $entityProducts = [];

	/** @var int */
	private $defaultStore;

	public function __construct(Entity\Base $entity)
	{
		$this->entity = $entity;

		$this->defaultStore = Catalog\StoreTable::getDefaultStoreId();

		$this->prepareEntityProducts();
	}

	private function getEntity(): Entity\Base
	{
		return $this->entity;
	}

	private function prepareEntityProducts(): void
	{
		$products = $this->getEntity()->getProducts();
		$entityProducts = $this->getEntity()->getEntityProducts();

		foreach ($products as $xmlId => $product)
		{
			$entityProduct = $entityProducts[$product->getId()];
			$entityProduct['QUANTITY'] = $product->getQuantity();

			$this->entityProducts[$xmlId] = $entityProduct;
		}
	}

	private function getEntityProducts(): array
	{
		return $this->entityProducts;
	}

	private function getEntityProductsByProductId(): array
	{
		return $this->getEntity()->getProductsByProductId();
	}

	/**
	 * Unreserves all entity products
	 *
	 * @return Main\Result
	 */
	public function unReserve(): Main\Result
	{
		$result = new Main\Result();

		$orderList = [];

		$order = $this->getEntity()->getOrder();
		if ($order)
		{
			$orderList[] = $order;
		}
		else
		{
			$orderList = $this->getEntityOrderList();
			if (count($orderList) === 0)
			{
				return $result;
			}
		}

		/** @var Crm\Order\Order $order */
		foreach ($orderList as $order)
		{
			$basket = $order->getBasket();
			if ($basket)
			{
				foreach ($basket as $basketItem)
				{
					/** @var Sale\ReserveQuantity $reserveQuantityCollection */
					foreach ($basketItem->getReserveQuantityCollection() as $reserveQuantityCollection)
					{
						$deleteResult = $reserveQuantityCollection->delete();
						if (!$deleteResult->isSuccess())
						{
							$result->addErrors($deleteResult->getErrors());
						}
					}
				}
			}
		}

		if ($result->isSuccess())
		{
			foreach ($orderList as $order)
			{
				if ($order->isChanged())
				{
					$saveOrderResult = $order->save();
					if (!$saveOrderResult->isSuccess())
					{
						$result->addErrors($saveOrderResult->getErrors());
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Tries ship entity products
	 *
	 * @return Main\Result
	 */
	public function ship(): Main\Result
	{
		$result = new Main\Result();

		if (empty($this->getEntityProducts()))
		{
			return $result;
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$orderList = $this->getEntityOrderList();

		// get products and their quantity from shipments
		$orderProductList = $this->getOrderShipmentProductList($orderList);

		// search difference between entity and shipments products
		$diffProducts = $this->getDifferenceBetweenProducts(
			$this->getEntityProductsByProductId(),
			$orderProductList
		);

		// add products to new order
		if ($diffProducts)
		{
			// create new order
			if (\CCrmSaleHelper::isWithOrdersMode() || count($orderList) === 0)
			{
				$order = $this->createOrder();
				if (!$order)
				{
					$result->addError(
						new Main\Error('Error while creating order', self::CREATE_ORDER_ERROR_CODE)
					);
					return $result;
				}

				$orderList[] = $order;
			}

			$lastOrder = end($orderList);
			$addEntityProductsToOrderForShipResult = $this->addEntityProductsToOrderForShip($lastOrder, $diffProducts);
			if (!$addEntityProductsToOrderForShipResult->isSuccess())
			{
				$result->addErrors($addEntityProductsToOrderForShipResult->getErrors());
				return $result;
			}
		}

		// set store from product or default store
		foreach ($orderList as $order)
		{
			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				if ($shipment->isShipped())
				{
					continue;
				}

				/** @var Crm\Order\ShipmentItem $shipmentItem */
				foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
				{
					if (!$shipmentItem->getShipmentItemStoreCollection()->isEmpty())
					{
						continue;
					}

					$storeId = null;
					$basketXmlId = (string)$shipmentItem->getBasketItem()->getField('XML_ID');
					$product = $this->getEntity()->getProducts()[$basketXmlId];
					if ($product)
					{
						$storeId = $product->getStoreId();
					}

					if (!$storeId)
					{
						$storeId = $this->defaultStore;
					}

					$fillShipmentItemStoreResult = $this->fillShipmentItemStore($shipmentItem, $storeId);
					if (!$fillShipmentItemStoreResult->isSuccess())
					{
						$result->addErrors($fillShipmentItemStoreResult->getErrors());
						return $result;
					}
				}
			}
		}

		$checkAvailabilityProductsOnStore = $this->checkAvailabilityProductsOnStore($orderList);
		if (!$checkAvailabilityProductsOnStore->isSuccess())
		{
			$result->addErrors($checkAvailabilityProductsOnStore->getErrors());
			return $result;
		}

		foreach ($orderList as $order)
		{
			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				$hasItems = $shipment->getShipmentItemCollection()->count() > 0;
				$isCanceled = !$shipment->isShipped() && $shipment->getField('EMP_DEDUCTED_ID');

				if ($hasItems && !$isCanceled && !$shipment->isShipped())
				{
					$setResult = $shipment->setFields([
						'IS_REALIZATION' => 'Y',
						'DEDUCTED' => 'Y',
					]);
					if (!$setResult->isSuccess())
					{
						$result->addErrors($setResult->getErrors());
					}
				}
			}
		}

		if ($result->isSuccess())
		{
			foreach ($orderList as $order)
			{
				if ($order->isChanged())
				{
					$saveOrderResult = $order->save();
					if (!$saveOrderResult->isSuccess())
					{
						$result->addErrors($saveOrderResult->getErrors());
					}
				}
			}
		}

		if ($needEnableAutomation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		return $result;
	}

	/**
	 * @return Crm\Order\Order[]
	 */
	private function getEntityOrderList(): array
	{
		static $orderList = [];
		if ($orderList)
		{
			return $orderList;
		}

		$bindingResult = Crm\Order\EntityBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=OWNER_ID' => $this->getEntity()->getOwnerId(),
				'=OWNER_TYPE_ID' => $this->getEntity()->getOwnerTypeId(),
			],
			'order' => ['ORDER_ID' => 'ASC'],
		]);
		while ($bindingData = $bindingResult->fetch())
		{
			$order = Crm\Order\Order::load($bindingData['ORDER_ID']);
			if ($order)
			{
				$orderList[] = $order;
			}
		}

		return $orderList;
	}

	/**
	 * @return Crm\Order\Order|null
	 */
	private function createOrder(): ?Crm\Order\Order
	{
		$order = $this->getEntity()->createOrderByEntity();
		if ($order && $order->getContactCompanyCollection())
		{
			$order->getContactCompanyCollection()->disableAutoCreationMode();
		}

		return $order;
	}

	/**
	 * @param Crm\Order\Order $order
	 * @param array $products
	 * @return Main\Result
	 */
	private function addEntityProductsToOrderForShip(Crm\Order\Order $order, array $products = []): Main\Result
	{
		if (!$products)
		{
			$products = $this->getEntityProducts();
		}

		$productManager = new ProductManager(
			$this->getEntity()->getOwnerTypeId(),
			$this->getEntity()->getOwnerId()
		);

		return $productManager
			->setOrder($order)
			->addEntityProductsToOrderForShip($products)
		;
	}

	private function fillShipmentItemStore(Crm\Order\ShipmentItem $shipmentsItem, int $storeId): Main\Result
	{
		$result = new Main\Result();

		/** @var Crm\Order\ShipmentItemStoreCollection $shipmentItemStoreCollection */
		$shipmentItemStoreCollection = $shipmentsItem->getShipmentItemStoreCollection();

		$fields = [
			'BASKET_ID' => $shipmentsItem->getBasketId(),
			'STORE_ID' => $storeId,
			'QUANTITY' => $shipmentsItem->getQuantity(),
			'ORDER_DELIVERY_BASKET_ID' => $shipmentsItem->getId(),
		];
		$shipmentItemStore = $shipmentItemStoreCollection->createItem($shipmentsItem->getBasketItem());
		$setFieldResult = $shipmentItemStore->setFields($fields);
		if (!$setFieldResult->isSuccess())
		{
			$result->addErrors($setFieldResult->getErrors());
		}

		return $result;
	}

	private function checkAvailabilityProductsOnStore(array $orderList): Main\Result
	{
		$result = new Main\Result();

		/** @var Sale\Reservation\BasketReservationService $basketReservation */
		$basketReservation = Main\DI\ServiceLocator::getInstance()->get('sale.basketReservation');

		/** @var Crm\Order\Order $order */
		foreach ($orderList as $order)
		{
			$products = [];

			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				$isCanceled = !$shipment->isShipped() && $shipment->getField('EMP_DEDUCTED_ID');
				if ($isCanceled || $shipment->isShipped())
				{
					continue;
				}

				/** @var Crm\Order\ShipmentItem $item */
				foreach ($shipment->getShipmentItemCollection() as $item)
				{
					/** @var Crm\Order\ShipmentItemStore $shipmentItemStore */
					foreach ($item->getShipmentItemStoreCollection() as $shipmentItemStore)
					{
						$basketCode = $item->getBasketCode();
						$productId = $item->getProductId();
						$storeId = $shipmentItemStore->getStoreId();
						$quantity = $shipmentItemStore->getQuantity();

						if (isset($products[$basketCode]) && $products[$basketCode]['storeId'] === $storeId)
						{
							$products[$basketCode]['quantity'] += $quantity;
						}
						else
						{
							$products[$basketCode] = [
								'quantity' => $quantity,
								'storeId' => $storeId,
								'productId' => $productId,
							];
						}
					}
				}
			}

			foreach ($products as $basketCode => $product)
			{
				$storeId = $product['storeId'];
				$quantity = $product['quantity'];
				$productId = $product['productId'];

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
					$result->addError(
						new Main\Error("For product with id {$productId} quantity in shipment more than store")
					);
				}
			}
		}

		return $result;
	}

	/**
	 * @param Crm\Order\Order[] $orderList
	 * @return array
	 */
	private function getOrderShipmentProductList(array $orderList): array
	{
		$orderProductList = [];
		foreach ($orderList as $order)
		{
			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				/** @var Crm\Order\ShipmentItem $shipmentItem */
				foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
				{
					$basketItem = $shipmentItem->getBasketItem();
					$productId = $basketItem->getProductId();

					$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
					if ($shipmentItemStoreCollection->isEmpty())
					{
						$quantity = $shipmentItem->getQuantity();

						if (isset($orderProductList[$productId]))
						{
							$orderProductList[$productId]['QUANTITY'] += $quantity;
							$orderProductList[$productId]['STORE_LIST'][$this->defaultStore] += $quantity;
						}
						else
						{
							$orderProductList[$productId] = [
								'QUANTITY' => $quantity,
								'STORE_LIST' => [
									$this->defaultStore => $quantity,
								],
							];
						}
					}
					else
					{
						/** @var Crm\Order\ShipmentItemStore $shipmentItemStore */
						foreach ($shipmentItemStoreCollection as $shipmentItemStore)
						{
							$shipmentStoreId = $shipmentItemStore->getStoreId();
							$quantity = $shipmentItemStore->getQuantity();

							if (isset($orderProductList[$productId]))
							{
								$orderProductList[$productId]['QUANTITY'] += $quantity;

								if (isset($orderProductList[$productId]['STORE_LIST'][$shipmentStoreId]))
								{
									$orderProductList[$productId]['STORE_LIST'][$shipmentStoreId] += $quantity;
								}
								else
								{
									$orderProductList[$productId]['STORE_LIST'][$shipmentStoreId] = $quantity;
								}
							}
							else
							{
								$orderProductList[$productId] = [
									'QUANTITY' => $quantity,
									'STORE_LIST' => [
										$shipmentStoreId => $quantity,
									],
								];
							}
						}
					}
				}
			}
		}

		return $orderProductList;
	}

	private function getDifferenceBetweenProducts(array $entityProducts, array $orderProductList): array
	{
		$diffProducts = [];
		foreach ($entityProducts as $productId => $entityProduct)
		{
			if (isset($orderProductList[$productId]))
			{
				$quantityDiff = $entityProduct['QUANTITY'] - $orderProductList[$productId]['QUANTITY'];
				if ($quantityDiff <= 1e-10)
				{
					continue;
				}

				$entityProductStoreList = $entityProduct['STORE_LIST'];
				$orderProductStoreList = $orderProductList[$productId]['STORE_LIST'];

				$newStoreList = [];
				foreach ($entityProductStoreList as $storeId => $storeQuantity)
				{
					$orderProductStoreQuantity = $orderProductStoreList[$storeId] ?? null;
					if ($orderProductStoreQuantity)
					{
						$newStoreList[$storeId] = $storeQuantity - $orderProductStoreQuantity;
					}
					else
					{
						$newStoreList[$storeId] = $storeQuantity;
					}
				}

				$diffProduct = $entityProduct;
				$diffProduct['STORE_LIST'] = $newStoreList;

				$diffProducts[$productId] = $diffProduct;
			}
			else
			{
				$diffProducts[$productId] = $entityProduct;
			}
		}

		return $diffProducts;
	}
}

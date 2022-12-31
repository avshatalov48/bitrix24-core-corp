<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;

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

	/**
	 * Get entity products with grouped fields for ProductManager.
	 *
	 * @return array
	 */
	private function getEntityProductsForProductManager(): array
	{
		$result = [];

		$crmReserves = $this->getCrmReserves();

		foreach ($this->entityProducts as $basketXmlId => $entityProduct)
		{
			$storeId = (int)(
				$entityProduct['STORE_ID']
				?? $crmReserves[$entityProduct['ID']]
				?? $this->defaultStore
			);
			if (isset($result[$basketXmlId]))
			{
				$result[$basketXmlId]['QUANTITY'] += $entityProduct['QUANTITY'];

				if (isset($result[$basketXmlId]['STORE_LIST'][$storeId]))
				{
					$result[$basketXmlId]['STORE_LIST'][$storeId] += $entityProduct['QUANTITY'];
				}
				else
				{
					$result[$basketXmlId]['STORE_LIST'][$storeId] = $entityProduct['QUANTITY'];
				}
			}
			else
			{
				$result[$basketXmlId] = [
					'QUANTITY' => $entityProduct['QUANTITY'],
					'PRODUCT' => $entityProduct,
					'STORE_LIST' => [
						$storeId => $entityProduct['QUANTITY'],
					],
				];
			}
		}

		return $result;
	}

	/**
	 * Information on reserves that is stored in the crm.
	 *
	 * It is relevant for a situation when there is actually no reserve,
	 * but it needs to be written off from a specific warehouse.
	 *
	 * @return array
	 */
	private function getCrmReserves(): array
	{
		$productRowIds = array_column($this->entityProducts, 'ID');
		$productRowIds = array_filter($productRowIds);
		if (empty($productRowIds))
		{
			return [];
		}

		$rows = ProductRowReservationTable::getList([
			'select' => [
				'ROW_ID',
				'STORE_ID',
			],
			'filter' => [
				'=ROW_ID' => $productRowIds,
			],
		]);
		$rows = array_column($rows->fetchAll(), 'STORE_ID', 'ROW_ID');

		return $rows;
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
					/** @var Sale\ReserveQuantityCollection $reserveCollection */
					$reserveCollection = $basketItem->getReserveQuantityCollection();
					if ($reserveCollection)
					{
						/** @var Sale\ReserveQuantity $reserveQuantityCollection */
						foreach ($reserveCollection as $reserveQuantityCollection)
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
		$orderServiceList = $this->getOrderShipmentServiceList($orderList);

		// search difference between entity and shipments products
		$diffProducts = $this->getDifferenceBetweenProducts(
			$this->getEntityProductsForProductManager(),
			array_merge($orderProductList, $orderServiceList)
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
					if (!$shipmentItem->getBasketItem()->isReservableItem())
					{
						continue;
					}

					$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
					if ($shipmentItemStoreCollection && !$shipmentItemStoreCollection->isEmpty())
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
					if ($this->needSyncOrderClientWithEntity($order))
					{
						$this->syncOrderClientWithEntity($order);
					}

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
		$orderList = [];

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
		return $this->getEntity()->createOrderByEntity();
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
		if (!$shipmentItemStoreCollection)
		{
			return $result;
		}

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
				foreach ($shipment->getShipmentItemCollection()->getShippableItems() as $shipmentItem)
				{
					/** @var Crm\Order\BasketItem $basketItem */
					$basketItem = $shipmentItem->getBasketItem();

					$basketXmlId = $basketItem->getField('XML_ID');

					$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
					if ($shipmentItemStoreCollection && !$shipmentItemStoreCollection->isEmpty())
					{
						/** @var Crm\Order\ShipmentItemStore $shipmentItemStore */
						foreach ($shipmentItemStoreCollection as $shipmentItemStore)
						{
							$shipmentStoreId = $shipmentItemStore->getStoreId();
							$quantity = $shipmentItemStore->getQuantity();

							if (isset($orderProductList[$basketXmlId]))
							{
								$orderProductList[$basketXmlId]['QUANTITY'] += $quantity;

								if (isset($orderProductList[$basketXmlId]['STORE_LIST'][$shipmentStoreId]))
								{
									$orderProductList[$basketXmlId]['STORE_LIST'][$shipmentStoreId] += $quantity;
								}
								else
								{
									$orderProductList[$basketXmlId]['STORE_LIST'][$shipmentStoreId] = $quantity;
								}
							}
							else
							{
								$orderProductList[$basketXmlId] = [
									'QUANTITY' => $quantity,
									'STORE_LIST' => [
										$shipmentStoreId => $quantity,
									],
								];
							}
						}
					}
					else
					{
						$quantity = $shipmentItem->getQuantity();

						if (isset($orderProductList[$basketXmlId]))
						{
							$orderProductList[$basketXmlId]['QUANTITY'] += $quantity;
							$orderProductList[$basketXmlId]['STORE_LIST'][$this->defaultStore] += $quantity;
						}
						else
						{
							$orderProductList[$basketXmlId] = [
								'QUANTITY' => $quantity,
								'STORE_LIST' => [
									$this->defaultStore => $quantity,
								],
							];
						}
					}
				}
			}
		}

		return $orderProductList;
	}

	/**
	 * @param Crm\Order\Order[] $orderList
	 * @return array
	 */
	private function getOrderShipmentServiceList(array $orderList): array
	{
		$orderServiceList = [];
		foreach ($orderList as $order)
		{
			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				/** @var Crm\Order\ShipmentItem $shipmentItem */
				foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
				{
					if ($shipmentItem->isShippable())
					{
						continue;
					}

					$basketXmlId = $shipmentItem->getBasketItem()->getField('XML_ID');
					$quantity = $shipmentItem->getQuantity();

					if (isset($orderServiceList[$basketXmlId]))
					{
						$orderServiceList[$basketXmlId]['QUANTITY'] += $quantity;
					}
					else
					{
						$orderServiceList[$basketXmlId]['QUANTITY'] = $quantity;
					}
				}
			}
		}

		return $orderServiceList;
	}

	private function getDifferenceBetweenProducts(array $entityProducts, array $orderProductList): array
	{
		$diffProducts = [];
		foreach ($entityProducts as $basketXmlId => $entityProduct)
		{
			if (isset($orderProductList[$basketXmlId]))
			{
				$quantityDiff = $entityProduct['QUANTITY'] - $orderProductList[$basketXmlId]['QUANTITY'];
				if ($quantityDiff <= 1e-10)
				{
					continue;
				}

				$entityProductStoreList = $entityProduct['STORE_LIST'] ?? [];
				$orderProductStoreList = $orderProductList[$basketXmlId]['STORE_LIST'] ?? [];

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

				$diffProducts[$basketXmlId] = $diffProduct;
			}
			else
			{
				$diffProducts[$basketXmlId] = $entityProduct;
			}
		}

		return $diffProducts;
	}

	/**
	 * Ñhecks if client synchronization is needed
	 * If order contains only new shipment - true
	 * Otherwise - false
	 *
	 * @param Crm\Order\Order $order
	 * @return bool
	 */
	private function needSyncOrderClientWithEntity(Crm\Order\Order $order): bool
	{
		/** @var Crm\Order\Shipment $shipment */
		foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
		{
			if ((int)$shipment->getId() > 0)
			{
				return false;
			}
		}

		return true;
	}

	private function syncOrderClientWithEntity(Crm\Order\Order $order): void
	{
		$clientInfo = Crm\ClientInfo::createFromOwner(
			$this->getEntity()->getOwnerTypeId(),
			$this->getEntity()->getOwnerId()
		);

		if ($clientInfo->isClientExists())
		{
			$contactCompanyCollection = $order->getContactCompanyCollection();
			if (!$contactCompanyCollection->isEmpty())
			{
				$contactCompanyCollection->clearCollection();
			}

			$clientData = $clientInfo->toArray();

			if ((int)($clientData['COMPANY_ID']) > 0)
			{
				/** @var Crm\Order\Company $company */
				$company = $contactCompanyCollection->createCompany();
				$company->setFields([
					'ENTITY_ID' => $clientData['COMPANY_ID'],
					'IS_PRIMARY' => 'Y',
				]);
			}

			if (!empty($clientData['CONTACT_IDS']))
			{
				$contactIds = array_unique($clientData['CONTACT_IDS']);
				$firstClientKey = key($contactIds);
				foreach ($contactIds as $key => $itemId)
				{
					if ($itemId > 0)
					{
						$contact = $contactCompanyCollection->createContact();
						$contact->setFields([
							'ENTITY_ID' => $itemId,
							'IS_PRIMARY' => ($key === $firstClientKey) ? 'Y' : 'N',
						]);
					}
				}
			}
		}
	}
}

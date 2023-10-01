<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketItemsSynchronizer;
use Bitrix\Crm\Order\OrderDealSynchronizer\FlowTrait;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\Reservation\ProductRowReservesSynchronizer;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Timeline\OrderController;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;
use CCrmSaleHelper;

/**
 * Synchronizer of the products of the order and the deal.
 *
 * Synchronization works only in the CRM mode without orders and occurs only if the products of the deal or the order are changed.
 *
 * Mechanics of products synchronization:
 * 1. when adding a product to a deal, a new basket item is added to the order, the received item is linked to the `crm` product row:
 * the `XML_ID` fields are filled in `b_sale_basket` and in `b_crm_product_row`;
 * 2. when editing a product in a deal (price, quantity, discount, the product itself, variation, etc.),
 * if the product is already in the order (checked by `XML_ID`), then the basket item is updated.
 * Otherwise, a new basket item is added;
 * 3. when the product is removed from the deal the linked basket item is deleted.
 * If there is no position, then nothing is deleted.
 *
 * @see \Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId
 * @see \Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId
 *
 * Before changing the products of the deal, the possibility of such actions in the order is checked (`verifyDealProducts` method).
 * There is no such check in the opposite direction.
 *
 * Places where the synchronizer is called:
 * 1. The events of saving the products of the deal: `OnBeforeCrmDealProductRowsSave` and `OnAfterCrmDealProductRowsSave`;
 * 2. After saving the crm order: `Bitrix\Crm\Order\Order::onAfterSave`;
 * 3. when saving a deal from a component, if inventory accounting elements are used: crm/install/components/bitrix/crm.deal.details/ajax.php
 *
 * For example create deal and update products by order:
 * ```php
 * $order = \Bitrix\Crm\Order\Order::load($orderId);
 *
 * $synchronizer = new \Bitrix\Crm\Order\OrderDealSynchronizer;
 * $synchronizer->createDealFromOrder($order);
 * $synchronizer->updateDealFromOrder($order);
 * ```
 *
 * For example create and update order products by deal:
 * ```php
 * $synchronizer = new \Bitrix\Crm\Order\OrderDealSynchronizer;
 * $synchronizer->createOrderFromDeal($dealId);
 * $synchronizer->updateOrderFromDeal($dealId);
 * ```
 */
class OrderDealSynchronizer
{
	use FlowTrait;

	/**
	 * A flag that determines whether synchronization is being performed.
	 *
	 * @var bool
	 */
	private bool $isSupportSync;

	/**
	 * @param bool|null $isSupportSync if sets `null` - init automatically.
	 */
	public function __construct(?bool $isSupportSync = null)
	{
		$this->isSupportSync = $isSupportSync ?? !CCrmSaleHelper::isWithOrdersMode();
	}

	/**
	 * Fills in the missing fields on product from the `b_crm_product_row` table.
	 *
	 * @param array $dealProducts
	 *
	 * @return array
	 */
	private static function fillingProducts(array $dealProducts): array
	{
		if (empty($dealProducts))
		{
			return [];
		}

		$map = [];
		$dealProductsIds = [];

		foreach($dealProducts as $index => $dealProduct)
		{
			$id = (int)($dealProduct['ID'] ?? 0);
			if ($id)
			{
				$dealProductsIds[] = $id;
				$map[$id] = $dealProduct;
			}
			else
			{
				$map['n' . $index] = $dealProduct;
			}
		}

		$currentDealProducts = ProductRowTable::getList([
			'filter' => [
				'=ID' => $dealProductsIds,
			],
		]);
		foreach ($currentDealProducts as $dealProduct)
		{
			$id = (int)$dealProduct['ID'];
			if (isset($map[$id]))
			{
				$map[$id] += $dealProduct;
			}
		}

		return array_values($map);
	}

	/**
	 * Create order from deal.
	 *
	 * @param int $dealId
	 *
	 * @return Order|null
	 */
	private function createOrder(int $dealId): ?Order
	{
		return (new OrderCreator($dealId))->create();
	}

	/**
	 * Update order fields by deal.
	 *
	 * @param Order $order
	 * @param int $dealId
	 *
	 * @return void
	 */
	private function updateOrder(Order $order, int $dealId): void
	{
		(new OrderCreator($dealId))->update($order->getId());
	}

	/**
	 * Create deal from order.
	 * Don't create new deal if order already binded to deal.
	 *
	 * @param Order $order
	 *
	 * @return void
	 */
	public function createDealFromOrder(Order $order): void
	{
		if (!$this->isSupportSync)
		{
			return;
		}

		$binding = $order->getEntityBinding();
		if ($binding || $this->isLockedOrder($order->getId()))
		{
			return;
		}

		$dealId = null;
		try
		{
			$this->lockOrder($order->getId());

			$dealId = (new DealCreator($order))->create();
			if ($dealId)
			{
				$binding = $order->createEntityBinding();
				$binding->setField('OWNER_ID', $dealId);
				$binding->setField('OWNER_TYPE_ID', CCrmOwnerType::Deal);
				$binding->markEntityAsNew();
				$binding->save();

				OrderController::getInstance()->markDealAsCreatedFromOrder(
					$order->getId(),
					$binding->getOwnerId()
				);

				$this->lockDeal($dealId);
				$this->syncDealProductsByOrder($dealId, $order);
			}
		}
		finally
		{
			$this->unlockOrder($order->getId());
			if ($dealId)
			{
				$this->unlockDeal($dealId);
			}
		}

	}

	/**
	 * Update deal products from order.
	 * A deal is not created if the order is not binded to the deal at the time of synchronization.
	 *
	 * @param Order $order
	 *
	 * @return void
	 */
	public function updateDealFromOrder(Order $order): void
	{
		if (!$this->isSupportSync)
		{
			return;
		}

		$binding = $order->getEntityBinding();
		if (!$binding || $this->isLockedOrder($order->getId()))
		{
			return;
		}

		$dealId = $binding->getOwnerId();
		$orderId = $order->getId();

		try
		{
			$this->lockOrder($orderId);
			$this->lockDeal($dealId);

			$this->syncDealProductsByOrder($dealId, $order);
		}
		finally
		{
			$this->unlockOrder($orderId);
			$this->unlockDeal($dealId);
		}
	}

	/**
	 * Checking the list of products of the deal, whether they can be saved in the order.
	 *
	 * @param int $dealId
	 * @param array $dealProducts
	 *
	 * @return Result
	 */
	public function verifyDealProducts(int $dealId, array $dealProducts): Result
	{
		if (!$this->isSupportSync)
		{
			return new Result();
		}

		if ($this->isLockedDeal($dealId))
		{
			return new Result();
		}

		$order = $this->getOrderBindedToDeal($dealId);
		if (!$order)
		{
			$order = (new OrderCreator($dealId))->build();
		}

		$dealProducts = self::fillingProducts($dealProducts);
		$synchronizer = new ProductRowSynchronizer($order, $dealProducts);
		return $synchronizer->verify();
	}

	/**
	 * Create order from deal.
	 * If the deal is already binded to the order, a new order will not be created and synchronization will not be performed.
	 *
	 * @param int $dealId
	 *
	 * @return void
	 */
	public function createOrderFromDeal(int $dealId): void
	{
		if (!$this->isSupportSync)
		{
			return;
		}

		$order = $this->getOrderBindedToDeal($dealId);
		if ($order || $this->isLockedDeal($dealId))
		{
			return;
		}

		$orderId = null;
		try
		{
			$this->lockDeal($dealId);

			$order = $this->createOrder($dealId);
			if ($order)
			{
				$orderId = $order->getId();
				$this->lockOrder($orderId);
				$this->syncOrderProductsByDeal($order, $dealId);
			}
		}
		finally
		{
			$this->unlockDeal($dealId);
			if ($orderId)
			{
				$this->unlockOrder($orderId);
			}
		}
	}

	/**
	 * Update order from deal.
	 * If the deal is not binded to the order, a new order will not be created and synchronization will not be performed.
	 *
	 * @param int $dealId
	 *
	 * @return void
	 */
	public function updateOrderFromDeal(int $dealId): void
	{
		if (!$this->isSupportSync)
		{
			return;
		}

		$order = $this->getOrderBindedToDeal($dealId);
		if (!$order || $this->isLockedDeal($dealId))
		{
			return;
		}

		try
		{
			$this->lockDeal($dealId);
			$this->lockOrder($order->getId());

			$this->updateOrder($order, $dealId);
			$this->syncOrderProductsByDeal($order, $dealId);
		}
		finally
		{
			$this->unlockDeal($dealId);
			$this->unlockOrder($order->getId());
		}
	}

	/**
	 * Update order reserves from reservations result of deal.
	 *
	 * If the order not exists, creates it.
	 *
	 * @param int $dealId
	 * @param ReservationResult $result
	 * @param bool $beforeSyncProducts
	 *
	 * @return void
	 */
	public function syncOrderReservesFromDeal(int $dealId, ReservationResult $result, bool $beforeSyncProducts = false): void
	{
		if (!$this->isSupportSync)
		{
			return;
		}

		if (empty($result->getChangedReserveInfos()))
		{
			return;
		}

		$order = $this->getOrderBindedToDeal($dealId);
		if (!$order)
		{
			$this->createOrderFromDeal($dealId);
			$order = $this->getOrderBindedToDeal($dealId);
		}

		if (!$order)
		{
			return;
		}

		try
		{
			$this->lockOrder($order->getId());

			if ($beforeSyncProducts)
			{
				$this->syncOrderProductsByDeal($order, $dealId);
			}

			$this->syncOrderProductsReservesByReservationResult($order, $dealId, $result);
		}
		finally
		{
			$this->unlockOrder($order->getId());
		}
	}

	/**
	 * Get order binded to deal.
	 *
	 * @param int $dealId
	 *
	 * @return Order|null
	 */
	private function getOrderBindedToDeal(int $dealId): ?Order
	{
		$row = OrderEntityTable::getRow([
			'select' => [
				'ORDER_ID',
			],
			'filter' => [
				'=OWNER_ID' => $dealId,
				'=OWNER_TYPE_ID' => CCrmOwnerType::Deal,
			],
		]);
		if ($row)
		{
			return Order::load($row['ORDER_ID']);
		}
		return null;
	}

	/**
	 * Synchronize products of order from deal (deal -> order).
	 *
	 * @param Order $order
	 * @param int $dealId
	 *
	 * @return void
	 * @throws SystemException is not install 'sale' module
	 */
	private function syncOrderProductsByDeal(Order $order, int $dealId): void
	{
		$dealProducts = ProductRowTable::getList([
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::Deal,
				'=OWNER_ID' => $dealId,
			],
		]);
		$synchronizer = new ProductRowSynchronizer($order, $dealProducts->fetchAll());
		$synchronizer->syncAndSave(false);
	}

	/**
	 * Synchronize products of deal from order (order -> deal).
	 *
	 * @param int $dealId
	 * @param Order $order
	 *
	 * @return void
	 */
	private function syncDealProductsByOrder(int $dealId, Order $order): void
	{
		$existDealProducts = ProductRowTable::getList([
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::Deal,
				'=OWNER_ID' => $dealId,
			],
		]);
		$synchronizer = new BasketItemsSynchronizer($order->getBasket(), $existDealProducts->fetchAll());
		$synchronizer->syncAndSave($dealId);
	}

	/**
	 * Synchronize products reserves of deal from order (order -> deal).
	 *
	 * @param Order $order
	 * @param int $dealId
	 * @param ReservationResult $result
	 *
	 * @return void
	 */
	private function syncOrderProductsReservesByReservationResult(Order $order, int $dealId, ReservationResult $result): void
	{
		$synchronizer = new ProductRowReservesSynchronizer($dealId, $order->getBasket(), $result);
		$synchronizer->syncAndSave($dealId);
	}
}

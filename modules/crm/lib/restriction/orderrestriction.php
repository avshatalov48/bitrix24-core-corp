<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order;
use Bitrix\Main\Loader;
use Bitrix\Sale\OrderTable;

class OrderRestriction extends Bitrix24AccessRestriction
{
	public const LIMIT_SLIDER_ID = 'limit_shop_100_orders';

	protected $validIdBound = null;

	public function __construct($name = '', $permitted = false, array $htmlInfo = null, array $popupInfo = null)
	{
		if (is_null($popupInfo))
		{
			$popupInfo = ['ID' => static::LIMIT_SLIDER_ID];
		}

		parent::__construct($name, $permitted, $htmlInfo, $popupInfo);

		$this->init();
	}

	protected function init()
	{
		$orderLimit = static::getOrderLimit();

		$validIdBound = OrderTable::getList([
			'select' => ['ID'],
			'order' => ['ID' => 'ASC'],
			'limit' => 1,
			'offset' => $orderLimit,
			'cache' => ['ttl' => 300],
		])->fetch()['ID'];
		$this->validIdBound = (int)$validIdBound;
	}

	/**
	 * @return int
	 */
	public static function getOrderLimit(): int
	{
		$limit = Bitrix24Manager::getVariable('sale_orders_limit');

		return $limit ?: 0;
	}

	/**
	 * @return bool
	 */
	public static function isOrderLimitReached(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			$limit = static::getOrderLimit();
			$count = Order\Order::getList([
				'select' => ['CNT'],
				'runtime' => [
					new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
				],
				'cache' => ['ttl' => 600]
			])->fetch()['CNT'];
			return ($limit > 0) && ((int)$count >= $limit);
		}

		return false;
	}

	public function isItemRestricted(ItemIdentifier $item)
	{
		if (!static::isOrderLimitReached())
		{
			return false;
		}

		$entityId = $item->getEntityId();
		$entityTypeId = $item->getEntityTypeId();

		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return $this->isOrderAboveLimit($entityId);
		}

		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$boundOrders = \Bitrix\Crm\Binding\OrderDealTable::getDealOrders($entityId);
			foreach ($boundOrders as $orderId)
			{
				if ($this->isOrderAboveLimit((int)$orderId))
				{
					return true;
				}
			}
		}

		return false;
	}

	protected function isOrderAboveLimit($orderId)
	{
		return $this->validIdBound && ($orderId >= (int)$this->validIdBound);
	}
}

<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order;
use Bitrix\Sale;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;

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

		$parameters = [
			'select' => ['ID'],
			'filter' => [
				'!=TRADING_PLATFORM.TRADING_PLATFORM.CODE' => Order\TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE
			],
			'order' => ['ID' => 'ASC'],
			'limit' => 1,
			'offset' => $orderLimit,
			'cache' => ['ttl' => 300],
			'runtime' => []
		];

		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			$parameters['runtime'][] = new Entity\ReferenceField(
				'ENTITY',
				OrderEntityTable::class,
				['=ref.ORDER_ID' => 'this.ID'],
				['join_type' => 'INNER']
			);
		}

		$validIdBound = Order\Order::getList($parameters)->fetch()['ID'];
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
		if (
			Loader::includeModule('bitrix24')
			&& Loader::includeModule('sale')
		)
		{
			$limit = static::getOrderLimit();
			if ($limit <= 0)
			{
				return false;
			}

			$parameters = [
				'select' => ['CNT'],
				'filter' => [
					'!=TRADING_PLATFORM.TRADING_PLATFORM.CODE' => Order\TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE
				],
				'runtime' => [
					new Entity\ExpressionField('CNT', 'COUNT(1)')
				],
				'cache' => ['ttl' => 600]
			];

			if (!\CCrmSaleHelper::isWithOrdersMode())
			{
				$parameters['runtime'][] = new Entity\ReferenceField(
					'ENTITY',
					OrderEntityTable::class,
					['=ref.ORDER_ID' => 'this.ID'],
					['join_type' => 'INNER']
				);
			}

			$count = Order\Order::getList($parameters)->fetch()['CNT'];

			return (int)$count >= $limit;
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
		elseif (
			$entityTypeId === \CCrmOwnerType::Deal
			|| \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
		)
		{
			$boundOrders = OrderEntityTable::getOrderIdsByOwner($entityId, $entityTypeId);
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
		if ($this->validIdBound && ($orderId >= (int)$this->validIdBound))
		{
			$data = Sale\TradingPlatform\OrderTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=ORDER_ID' => $orderId,
					'=TRADING_PLATFORM.CODE' => Order\TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE
				]
			])->fetch();

			return !$data;
		}

		return false;
	}
}

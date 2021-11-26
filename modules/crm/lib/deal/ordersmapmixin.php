<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order\DealBinding;

trait OrdersMapMixin
{
	/**
	 * Returns map [orderId => related dealId]
	 * @param array $dealIds
	 * @return array<int, int>
	 */
	public function getOrderToDealMap(array $dealIds): array
	{
		if (count($dealIds) === 0)
		{
			return [];
		}

		$dealOrders = DealBinding::getList([
			'select' => ['ORDER_ID', 'DEAL_ID'],
			'filter' => [
				'=DEAL_ID' => $dealIds
			],
		]);

		$orderToDealMap = [];

		while ($binding = $dealOrders->fetch())
		{
			$orderToDealMap[(int)$binding['ORDER_ID']] = (int)$binding['DEAL_ID'];
		}

		return $orderToDealMap;
	}
}

<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order\EntityBinding;

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

		$dealOrders = EntityBinding::getList([
			'select' => ['ORDER_ID', 'OWNER_ID'],
			'filter' => [
				'=OWNER_ID' => $dealIds,
				'=OWNER_TYPE_ID' => \CCrmOwnerType::Deal
			],
		]);

		$orderToDealMap = [];

		while ($binding = $dealOrders->fetch())
		{
			$orderToDealMap[(int)$binding['ORDER_ID']] = (int)$binding['OWNER_ID'];
		}

		return $orderToDealMap;
	}
}

<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service\Broker;

/**
 * @method \Bitrix\Crm\Order\Order|null getById(int $id)
 * @method \Bitrix\Crm\Order\Order[] getBunchByIds(array $ids)
 */
class Order extends Broker
{
	protected function loadEntry(int $id)
	{
		return \Bitrix\Crm\Order\Order::load($id);
	}

	protected function loadEntries(array $ids): array
	{
		/** @var \Bitrix\Crm\Order\Order[] $list */
		$list = \Bitrix\Crm\Order\Order::loadByFilter([
			'filter' => ['@ID' => $ids],
		]);

		$orders = [];
		foreach ($list as $order)
		{
			$orders[$order->getId()] = $order;
		}

		return $orders;
	}
}

<?php

namespace Bitrix\Crm\Service\UserPermissions\Helper;

use Bitrix\Crm\ItemIdentifier;

class Shipment
{
	public static function getOrderIdByShipmentId(int $shipmentId): int
	{
		if ($shipmentId > 0)
		{
			$result = \Bitrix\Crm\Order\Shipment::getList([
				'filter' => [
					'=ID' => $shipmentId,
				],
				'select' => [
					'ID',
					'ORDER_ID',
				],
				'limit' => 1
			]);

			$shipmentData = $result->fetch();
			$orderId = $shipmentData['ORDER_ID'];
			if ($orderId <= 0)
			{
				return 0;
			}

			return $orderId;
		}

		return 0;
	}

	public static function getBoundIdentifierByEntityId(int $id): ?ItemIdentifier
	{
		$orderId = self::getOrderIdByShipmentId($id);

		return $orderId > 0 ? Order::getBoundIdentifierByOrderId($orderId) : null;
	}
}

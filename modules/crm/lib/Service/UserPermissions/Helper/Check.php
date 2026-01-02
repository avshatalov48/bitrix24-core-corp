<?php

namespace Bitrix\Crm\Service\UserPermissions\Helper;

use Bitrix\Main\Loader;
use Bitrix\Crm\ItemIdentifier;

class Check
{
	public static function getOrderIdByCheckId(int $checkId): int
	{
		if ($checkId > 0 && Loader::includeModule('sale'))
		{
			$check = \Bitrix\Sale\Cashbox\CheckManager::getObjectById($checkId);

			return (int)$check?->getField('ORDER_ID');
		}

		return 0;
	}

	public static function getBoundIdentifierByEntityId(int $id): ?ItemIdentifier
	{
		$orderId = self::getOrderIdByCheckId($id);

		return $orderId > 0 ? Order::getBoundIdentifierByOrderId($orderId) : null;
	}
}

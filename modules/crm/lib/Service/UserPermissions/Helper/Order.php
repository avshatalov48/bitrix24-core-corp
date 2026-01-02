<?php

namespace Bitrix\Crm\Service\UserPermissions\Helper;

use Bitrix\Crm\ItemIdentifier;

class Order {
	public static function getBoundIdentifierByOrderId(int $orderId): ?ItemIdentifier
	{
		$order = \Bitrix\Crm\Order\Order::load($orderId);
		if (!$order)
		{
			return null;
		}

		$binding = $order->getEntityBinding();
		if (!$binding)
		{
			return null;
		}

		return ItemIdentifier::createByParams($binding->getOwnerTypeId(), $binding->getOwnerId());
	}
}

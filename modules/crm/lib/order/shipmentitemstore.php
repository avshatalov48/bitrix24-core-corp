<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentItemStore
 * @package Bitrix\Crm\Order
 */
class ShipmentItemStore extends Sale\ShipmentItemStore
{
	protected function needMoveReserve(): bool
	{
		return \CCrmSaleHelper::isWithOrdersMode();
	}
}
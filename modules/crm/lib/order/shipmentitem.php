<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentItem
 * @package Bitrix\Crm\Order
 */
class ShipmentItem extends Sale\ShipmentItem
{
}
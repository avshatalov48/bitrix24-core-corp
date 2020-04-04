<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentItemCollection
 * @package Bitrix\Crm\Order
 */
class ShipmentItemCollection extends Sale\ShipmentItemCollection
{
}
<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentItemStoreCollection
 * @package Bitrix\Crm\Order
 */
class ShipmentItemStoreCollection extends Sale\ShipmentItemStoreCollection
{
}
<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentProperty
 * @package Bitrix\Crm\Order
 */
class ShipmentProperty extends Sale\ShipmentProperty
{

}
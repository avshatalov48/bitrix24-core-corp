<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentPropertyValue
 * @package Bitrix\Crm\Order
 */
class ShipmentPropertyValue extends Sale\ShipmentPropertyValue
{

}
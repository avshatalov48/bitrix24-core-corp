<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentPropertyValueCollection
 * @package Bitrix\Crm\Order
 */
class ShipmentPropertyValueCollection extends Sale\ShipmentPropertyValueCollection
{

}
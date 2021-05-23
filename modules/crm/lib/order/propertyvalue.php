<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PropertyValueCollection
 * @package Bitrix\Crm\Order
 */
class PropertyValue extends Sale\PropertyValue
{

}
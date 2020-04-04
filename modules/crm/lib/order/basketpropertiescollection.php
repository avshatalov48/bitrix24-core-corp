<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class BasketPropertiesCollection
 * @package Bitrix\Crm\Order
 */
class BasketPropertiesCollection extends Sale\BasketPropertiesCollection
{
}
<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class OrderDiscount
 * @package Bitrix\Crm\Order
 */
class OrderDiscount extends Sale\OrderDiscount
{
}
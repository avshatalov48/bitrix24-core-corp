<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Basket
 * @package Bitrix\Crm\Order
 */
class Basket extends Sale\Basket
{
}
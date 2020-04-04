<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class BasketItem
 * @package Bitrix\Crm\Order
 */
class BasketItem extends Sale\BasketItem
{
}
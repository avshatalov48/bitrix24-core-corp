<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Tax
 * @package Bitrix\Crm\Order
 */
class Tax extends Sale\Tax
{
}
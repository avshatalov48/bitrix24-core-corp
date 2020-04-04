<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Property
 * @package Bitrix\Crm\Order
 */
class Property extends Sale\Property
{

}
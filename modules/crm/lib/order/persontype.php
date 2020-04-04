<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PersonType
 * @package Bitrix\Crm\Order
 */
class PersonType extends Sale\PersonType
{
}
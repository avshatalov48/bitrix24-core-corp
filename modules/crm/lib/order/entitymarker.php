<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class EntityMarker
 * @package Bitrix\Crm\Order
 */
class EntityMarker extends Sale\EntityMarker
{
}
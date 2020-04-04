<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class TradeBindingEntity
 * @package Bitrix\Crm\Order
 */
class TradeBindingEntity extends Sale\TradeBindingEntity
{
}
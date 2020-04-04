<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class TradeBindingCollection
 * @package Bitrix\Crm\Order
 */
class TradeBindingCollection extends Sale\TradeBindingCollection
{

}
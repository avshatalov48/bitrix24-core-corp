<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class TradeBindingEntity
 * @package Bitrix\Crm\Invoice
 */
class TradeBindingEntity extends Sale\TradeBindingEntity
{
}
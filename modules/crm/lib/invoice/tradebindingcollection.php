<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class TradeBindingCollection
 * @package Bitrix\Crm\Invoice
 */
class TradeBindingCollection extends Sale\TradeBindingCollection
{

}
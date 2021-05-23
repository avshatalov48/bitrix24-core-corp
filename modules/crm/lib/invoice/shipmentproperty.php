<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentProperty
 * @package Bitrix\Crm\Invoice
 */
class ShipmentProperty extends Sale\ShipmentProperty
{

}
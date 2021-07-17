<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PayableShipmentItem
 * @package Bitrix\Crm\Order
 */
class PayableShipmentItem extends Sale\PayableShipmentItem
{

}
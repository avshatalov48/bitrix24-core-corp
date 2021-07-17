<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PayableItemCollection
 * @package Bitrix\Crm\Order
 */
class PayableItemCollection extends Sale\PayableItemCollection
{

}
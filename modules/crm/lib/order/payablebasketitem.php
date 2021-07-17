<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PayableBasketItem
 * @package Bitrix\Crm\Order
 */
class PayableBasketItem extends Sale\PayableBasketItem
{

}
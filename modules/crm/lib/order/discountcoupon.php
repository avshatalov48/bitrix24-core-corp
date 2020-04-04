<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Discount
 * @package Bitrix\Crm\Order
 */
class DiscountCoupon extends Sale\DiscountCouponsManager
{
}
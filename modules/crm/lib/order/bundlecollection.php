<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class BundleCollection
 * @package Bitrix\Crm\Order
 */
class BundleCollection extends Sale\BundleCollection
{
}
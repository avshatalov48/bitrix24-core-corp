<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PropertyVariant
 * @package Bitrix\Crm\Order
 */
class PropertyVariant
{
	public static function getList(array $params = [])
	{
		return Sale\Internals\OrderPropsVariantTable::getList($params);
	}
}
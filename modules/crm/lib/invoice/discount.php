<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Discount
 * @package Bitrix\Crm\Invoice
 */
class Discount extends Sale\Discount
{
	/**
	 * @throws Main\NotImplementedException
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}
}
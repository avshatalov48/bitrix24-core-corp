<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentItemStoreCollection
 * @package Bitrix\Crm\Invoice
 */
class ShipmentItemStoreCollection extends Sale\ShipmentItemStoreCollection
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param array $parameter
	 * @return Main\DB\ArrayResult
	 */
	public static function getList(array $parameter = array())
	{
		return new Main\DB\ArrayResult(array());
	}
}
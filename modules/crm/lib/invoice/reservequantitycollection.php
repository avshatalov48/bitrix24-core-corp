<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Crm\Order\ReserveQuantity;
use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

class ReserveQuantityCollection extends Sale\ReserveQuantityCollection
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected static function deleteInternal($primary)
	{
		return new Main\Entity\DeleteResult();
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return new Main\DB\ArrayResult([]);
	}
}
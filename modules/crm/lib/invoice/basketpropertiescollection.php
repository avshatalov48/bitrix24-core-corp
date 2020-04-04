<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Basket
 * @package Bitrix\Crm\Invoice
 */
class BasketPropertiesCollection extends Sale\BasketPropertiesCollection
{
	/**
	 * @throws Main\NotImplementedException
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * Load basket item properties.
	 *
	 * @param array $parameters	orm getList parameters.
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\BasketPropertyTable::getList($parameters);
	}

	/**
	 * Delete basket item properties.
	 *
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected static function delete($primary)
	{
		return Internals\BasketPropertyTable::delete($primary);
	}

}
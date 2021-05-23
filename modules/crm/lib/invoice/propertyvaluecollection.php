<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PropertyValueCollection
 * @package Bitrix\Crm\Invoice
 */
class PropertyValueCollection extends Sale\PropertyValueCollection
{
	/** @inherit  */
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
		return Internals\InvoicePropsValueTable::delete($primary);
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\InvoicePropsValueTable::getList($parameters);
	}

}
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
class PropertyValue extends Sale\PropertyValue
{
	/** @inherit  */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\InvoicePropsValueTable::getMap();
	}

	/**
	 * @param array $data
	 * @throws Main\NotImplementedException
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		return Internals\InvoicePropsValueTable::add($data);
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @throws Main\NotImplementedException
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		return Internals\InvoicePropsValueTable::update($primary, $data);
	}

	/**
	 * @param array $parameters
	 *
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\InvoicePropsValueTable::getList($parameters);
	}

}
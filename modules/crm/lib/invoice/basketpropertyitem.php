<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Main\Entity;
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
class BasketPropertyItem extends Sale\BasketPropertyItem
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param array $data
	 * @return Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		return Internals\BasketPropertyTable::add($data);
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		return Internals\BasketPropertyTable::update($primary, $data);
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\BasketPropertyTable::getMap();
	}

	/**
	 * @param array $parameters
	 * @return Main\ORM\Query\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getList(array $parameters = [])
	{
		return Internals\BasketPropertyTable::getList($parameters);
	}

}
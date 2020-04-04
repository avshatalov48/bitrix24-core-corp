<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class BasketItem
 * @package Bitrix\Crm\Invoice
 */
class BasketItem extends Sale\BasketItem
{
	/**
	 * @throws Main\NotImplementedException
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\BasketTable::getMap();
	}

	/**
	 * @param array $fields
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $fields)
	{
		return Internals\BasketTable::add($fields);
	}

	/**
	 * @param $primary
	 * @param array $fields
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $fields)
	{
		return Internals\BasketTable::update($primary, $fields);
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected function deleteInternal($primary)
	{
		return Internals\BasketTable::delete($primary);
	}

}
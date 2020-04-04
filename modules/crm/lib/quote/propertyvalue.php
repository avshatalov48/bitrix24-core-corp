<?php

namespace Bitrix\Crm\Quote;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PropertyValueCollection
 * @package Bitrix\Crm\Quote
 */
class PropertyValue extends Sale\PropertyValueBase
{
	/**
	 * @throws Main\NotImplementedException
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_QUOTE;
	}

	/**
	 * @param array $data
	 * @throws Main\NotImplementedException
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		// TODO: Implement addInternal() method.
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @throws Main\NotImplementedException
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		// TODO: Implement updateInternal() method.
	}

}
<?php

namespace Bitrix\Crm\Quote;

use Bitrix\Main\Entity;
use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Basket
 * @package Bitrix\Crm\Quote
 */
class BasketPropertyItem extends Sale\BasketPropertyItemBase
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_QUOTE;
	}

	/**
	 * @param array $data
	 * @return Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		// TODO: Implement addInternal() method.
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		// TODO: Implement updateInternal() method.
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return array(); // todo getFields from tablet
	}

}
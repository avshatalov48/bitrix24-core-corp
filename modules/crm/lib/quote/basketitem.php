<?php

namespace Bitrix\Crm\Quote;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class BasketItem
 * @package Bitrix\Crm\Quote
 */
class BasketItem extends Sale\BasketItemBase
{
	/**
	 * @throws Main\NotImplementedException
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_QUOTE;
	}

	/**
	 * @param array $fields
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $fields)
	{
		// TODO: Implement addInternal() method.
	}

	/**
	 * @param $primary
	 * @param array $fields
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $fields)
	{
		// TODO: Implement updateInternal() method.
	}

	/**
	 * @return float
	 */
	public function getReservedQuantity()
	{
		return 0;
	}

}
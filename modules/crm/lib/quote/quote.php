<?php

namespace Bitrix\Crm\Quote;

use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Quote
 * @package Bitrix\Crm\Quote
 */
class Quote extends Sale\OrderBase
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_QUOTE;
	}

	/**
	 * @return Sale\Tax
	 */
	protected function loadTax()
	{
		return null;
	}

	/**
	 * @return Sale\Discount
	 */
	protected function loadDiscount()
	{
		return null;
	}

	/**
	 * @return string
	 */
	protected static function getInitialStatus()
	{
		return '';
	}

	/**
	 * @param array $data
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		return new Main\Entity\AddResult();
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Main\Entity\UpdateResult
	 */
	protected static function updateInternal($primary, array $data)
	{
		return new Main\Entity\UpdateResult();
	}

	/**
	 * @return Main\EventResult
	 */
	public static function OnInitRegistryList()
	{
		$registry = array(
			REGISTRY_TYPE_CRM_QUOTE => array(
				Sale\Registry::ENTITY_ORDER => '\Bitrix\Crm\Quote\Quote',
				Sale\Registry::ENTITY_PROPERTY_VALUE => '\Bitrix\Crm\Quote\PropertyValue',
				Sale\Registry::ENTITY_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Quote\PropertyValueCollection',
				Sale\Registry::ENTITY_BASKET => '\Bitrix\Crm\Quote\Basket',
				Sale\Registry::ENTITY_BASKET_ITEM => '\Bitrix\Crm\Quote\BasketItem',
				Sale\Registry::ENTITY_BASKET_PROPERTIES_COLLECTION => '\Bitrix\Crm\Quote\BasketPropertiesCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTY_ITEM => '\Bitrix\Crm\Quote\BasketPropertyItem',
			)
		);

		return new Main\EventResult(Main\EventResult::SUCCESS, $registry);
	}
}
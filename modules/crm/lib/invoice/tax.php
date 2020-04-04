<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Tax
 * @package Bitrix\Crm\Invoice
 */
class Tax extends Sale\Tax
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return string
	 */
	protected static function getTaxClassName()
	{
		return \CCrmTaxEntity::class;
	}

	/**
	 * @return string
	 */
	protected static function getOrderTaxClassName()
	{
		return \CCrmInvoiceTax::class;
	}

}
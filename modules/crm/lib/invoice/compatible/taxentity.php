<?php

use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Attention!
 * Temporary solution. After refactoring this class will drop
 *
 * @deprecated
 * Class CCrmTaxEntity
 */
class CCrmTaxEntity extends CSaleTax
{
	/**
	 * @return string
	 */
	protected static function getOrderTaxEntityName()
	{
		return CCrmInvoiceTax::class;
	}

	/**
	 * @return string
	 */
	protected static function getHistoryEntityName()
	{
		return \Bitrix\Crm\Invoice\InvoiceHistory::class;
	}

}
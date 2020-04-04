<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class InvoiceHistory
 * @package Bitrix\Crm\Invoice
 */
class InvoiceHistory extends Sale\OrderHistory
{
	/**
	 * @param $fields
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 */
	protected static function addInternal($fields)
	{
		return Internals\InvoiceChangeTable::add($fields);
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected static function getList(array $parameters = array())
	{
		return Internals\InvoiceChangeTable::getList($parameters);
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 * @throws \Exception
	 */
	protected static function deleteInternal($primary)
	{
		return Internals\InvoiceChangeTable::delete($primary);
	}

}
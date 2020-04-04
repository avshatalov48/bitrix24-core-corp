<?php

namespace Bitrix\Crm\Invoice;

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
class Basket extends Sale\Basket
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param array $itemValues
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	protected function deleteInternal(array $itemValues)
	{
		$result  = new Sale\Result();

		$r = Internals\BasketTable::deleteWithItems($itemValues['ID']);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result|mixed
	 * @throws Main\ArgumentException
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\BasketTable::getList($parameters);
	}

	/**
	 * @param $idOrder
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function deleteNoDemand($idOrder)
	{
		$result = new Sale\Result();

		$itemsDataList = static::getList(
			array(
				"filter" => array("=ORDER_ID" => $idOrder),
				"select" => array("ID", "TYPE")
			)
		);

		while ($item = $itemsDataList->fetch())
		{
			$r = Internals\BasketTable::deleteWithItems($item['ID']);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

}
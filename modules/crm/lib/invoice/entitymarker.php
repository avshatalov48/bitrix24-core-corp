<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class EntityMarker
 * @package Bitrix\Crm\Invoice
 */
class EntityMarker extends Sale\EntityMarker
{
	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\EntityMarkerTable::getList($parameters);
	}

	/**
	 * @param $id
	 * @return Main\Entity\DeleteResult
	 * @throws \Exception
	 */
	public static function delete($id)
	{
		return Internals\EntityMarkerTable::delete($id);
	}

	/**
	 * @param array $data
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 */
	protected static function addInternal(array $data)
	{
		return Internals\EntityMarkerTable::add($data);
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Main\Entity\UpdateResult
	 * @throws \Exception
	 */
	protected static function updateInternal($primary, array $data)
	{
		return Internals\EntityMarkerTable::update($primary, $data);
	}
}
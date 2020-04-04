<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main\Entity;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Shipment
 * @package Bitrix\Crm\Invoice
 */
class Shipment extends Sale\Shipment
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}
	/**
	 * @param array $data
	 * @return Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		return Internals\ShipmentTable::add($data);
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		return Internals\ShipmentTable::update($primary, $data);
	}

	/**
	 * @param $primary
	 * @return Entity\DeleteResult
	 */
	protected static function deleteInternal($primary)
	{
		return Internals\ShipmentTable::deleteWithItems($primary);
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\ShipmentTable::getMap();
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getList(array $parameters)
	{
		return Internals\ShipmentTable::getList($parameters);
	}

	/**
	 * @return Sale\Result
	 */
	protected function saveExtraServices()
	{
		return new Sale\Result();
	}

	/**
	 * @return Sale\Result
	 */
	protected function saveStoreId()
	{
		return new Sale\Result();
	}

	/**
	 *	@return void
	 */
	protected function deleteDeliveryRequest()
	{
		return;
	}

	/**
	 * @return void
	 */
	protected function setDeliveryRequestMarker()
	{
		return;
	}

}
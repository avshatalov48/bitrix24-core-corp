<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentCollection
 * @package Bitrix\Crm\Invoice
 */
class ShipmentCollection extends Sale\ShipmentCollection
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\ShipmentTable::getList($parameters);
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected function deleteInternal($primary)
	{
		return Internals\ShipmentTable::deleteWithItems($primary);
	}

	/**
	 * @param $shipmentId
	 */
	protected function deleteExtraServiceInternal($shipmentId)
	{
		return;
	}

}
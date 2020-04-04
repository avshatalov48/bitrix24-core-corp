<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Sale\Internals;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Notify
 * @package Bitrix\Crm\Invoice
 */
class Notify extends Sale\Notify
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param Internals\Entity $entity
	 * @param $eventName
	 */
	public static function callNotify(Internals\Entity $entity, $eventName)
	{
		return;
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendOrderNew(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendOrderCancel(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendOrderPaid(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendOrderStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendShipmentStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendOrderAllowPayStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendShipmentTrackingNumberChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 */
	public static function sendShipmentAllowDelivery(Internals\Entity $entity)
	{
		return new Sale\Result();
	}
}
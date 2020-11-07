<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Sale\Internals;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Notify
 * @package Bitrix\Crm\Order
 */

class Notify extends Sale\Notify
{
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
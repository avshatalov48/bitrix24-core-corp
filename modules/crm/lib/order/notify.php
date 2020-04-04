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
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderNew(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderCancel(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderPaid(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderAllowPayStatusChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentTrackingNumberChange(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentAllowDelivery(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendPrintableCheck(Internals\Entity $entity)
	{
		return new Sale\Result();
	}

}
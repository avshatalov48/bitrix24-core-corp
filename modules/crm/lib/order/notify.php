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
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderNew(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendOrderNew($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderCancel(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendOrderCancel($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderPaid(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendOrderPaid($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderStatusChange(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendOrderStatusChange($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentStatusChange(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendShipmentStatusChange($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendOrderAllowPayStatusChange(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendOrderAllowPayStatusChange($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentTrackingNumberChange(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendShipmentTrackingNumberChange($entity);
	}

	/**
	 * @param Internals\Entity $entity
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 */
	public static function sendShipmentAllowDelivery(Internals\Entity $entity)
	{
		if (IsModuleInstalled('bitrix24'))
		{
			return new Sale\Result();
		}

		return parent::sendShipmentAllowDelivery($entity);
	}
}
<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

/**
 * Class Shipment
 * @package Bitrix\Crm\Order\EventsHandler
 * @internal
 */
final class Shipment
{
	private static $dealId;
	private static $needSynchronizeProductRows = false;

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnSaleShipmentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');

		if (!$shipment instanceof Crm\Order\Shipment)
		{
		 	return;
		}

		if (!$shipment->getFields()->isChanged('PRICE_DELIVERY'))
		{
			return;
		}

		/** @var Crm\Order\EntityBinding $binding */
		$binding = $shipment->getOrder()->getEntityBinding();
		if (
			!$binding
			|| $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal
		)
		{
			return;
		}

		$dealId = $binding->getOwnerId();
		if ($dealId === 0)
		{
			return;
		}

		\CCrmDeal::SynchronizeProductRows($dealId);
	}

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnBeforeSaleShipmentDeleted(Main\Event $event): void
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return;
		}

		$values = $event->getParameter('VALUES');

		$shipmentId = $values['ID'] ?? null;
		if ($shipmentId)
		{
			$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($shipmentId);
			if (!$shipment || $shipment->getPrice() <= 0)
			{
				return;
			}

			/** @var Crm\Order\EntityBinding $binding */
			$binding = $shipment->getOrder()->getEntityBinding();
			if (
				!$binding
				|| $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal
			)
			{
				return;
			}

			$dealId = $binding->getOwnerId();
			if ($dealId === 0)
			{
				return;
			}

			self::$needSynchronizeProductRows = true;
			self::$dealId = $dealId;
		}
	}

	/**
	 * @param Main\Event $event
	 * @return void
	 */
	public static function OnSaleShipmentDeleted(Main\Event $event): void
	{
		if (self::$needSynchronizeProductRows && self::$dealId)
		{
			\CCrmDeal::SynchronizeProductRows(self::$dealId);

			self::$needSynchronizeProductRows = false;
			self::$dealId = null;
		}
	}
}

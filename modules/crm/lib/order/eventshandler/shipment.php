<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;

final class Shipment
{
	public static function OnSaleShipmentEntitySaved(Main\Event $event)
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

		$dealBinding = $shipment->getOrder()->getDealBinding();
		if (!$dealBinding)
		{
			return;
		}

		$dealId = $dealBinding->getDealId();
		if ($dealId === 0)
		{
			return;
		}

		\CCrmDeal::SynchronizeProductRows($dealId);
	}
}
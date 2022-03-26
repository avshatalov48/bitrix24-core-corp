<?php

namespace Bitrix\Crm\Integration\Sale;

use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Timeline\ShipmentDocumentController;
use Bitrix\Main\Event;

class ShipmentDocumentAnalytics
{
	public static function onSaleShipmentEntitySaved(Event $event)
	{
		/* @var \Bitrix\Sale\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');

		$isSystem = $shipment->isSystem();
		$isRealization = $shipment->getField('IS_REALIZATION') === 'Y';
		if ($isSystem || !$isRealization)
		{
			return;
		}

		if ($shipment->isShipped())
		{
			AddEventToStatFile('crm', 'conductDocument', 'success', 'W');
		}
		else
		{
			AddEventToStatFile('crm', 'cancelDocument', 'success', 'W');
		}
	}
}

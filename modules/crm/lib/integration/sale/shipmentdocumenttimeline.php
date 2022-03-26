<?php

namespace Bitrix\Crm\Integration\Sale;

use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Timeline\ShipmentDocumentController;
use Bitrix\Main\Event;

class ShipmentDocumentTimeline
{
	public static function onSaleShipmentEntitySaved(Event $event)
	{
		$shipment = $event->getParameter('ENTITY');
		$oldValues = $event->getParameter('VALUES');
		if (!$shipment instanceof Shipment)
		{
			return;
		}

		$isSystem = $shipment->isSystem();
		$isRealization = $shipment->getField('IS_REALIZATION') === 'Y';
		if ($isSystem || !$isRealization)
		{
			return;
		}

		// is the shipment a new realization?
		$isNew =
			(array_key_exists('ID', $oldValues) && is_null($oldValues['ID']))
			|| (isset($oldValues['IS_REALIZATION']) && $oldValues['IS_REALIZATION'] === 'N')
		;

		if ($isNew)
		{
			$params = [
				'SHIPMENT' => $shipment,
				'ORDER' => $shipment->getOrder(),
			];
			ShipmentDocumentController::getInstance()->onCreate($shipment->getId(), $params);
			return;
		}

		$params = [
			'SHIPMENT' => $shipment,
			'ORDER' => $shipment->getOrder(),
		];
		ShipmentDocumentController::getInstance()->onModify($shipment->getId(), $params);
	}
}

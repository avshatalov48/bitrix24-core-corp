<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;

final class BasketReservation
{
	/**
	 * Adds reservation info to map
	 *
	 * @param Main\Event $event
	 */
	public static function OnSaleOrderSaved(Main\Event $event)
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');
		if (!($order instanceof Crm\Order\Order))
		{
			return;
		}

		$binding = $order->getEntityBinding();
		$entityTypeId = $binding ? $binding->getOwnerTypeId() : 0;
		$entityId = $binding ? $binding->getOwnerId() : 0;
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		ReservationService::getInstance()->mappingReservations($entityTypeId, $entityId, $order);
	}

	/**
	 * Deletes reservation info from map
	 *
	 * @param Main\Event $event
	 */
	public static function onAfterDelete(Main\Event $event)
	{
		$reservationId = (int)$event->getParameter('id')['ID'];
		if ($reservationId > 0)
		{
			self::deleteProductReservationMap($reservationId);
		}
	}

	private static function deleteProductReservationMap(int $reservationId): void
	{
		$productReservationMapIterator = Crm\Reservation\Internals\ProductReservationMapTable::getList([
			'select' => ['ID', 'PRODUCT_ROW_ID'],
			'filter' => [
				'=BASKET_RESERVATION_ID' => $reservationId,
			],
		]);
		while ($productReservationMapData = $productReservationMapIterator->fetch())
		{
			Crm\Reservation\Internals\ProductReservationMapTable::delete($productReservationMapData['ID']);
		}
	}
}

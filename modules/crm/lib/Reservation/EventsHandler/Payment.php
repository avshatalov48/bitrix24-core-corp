<?php

namespace Bitrix\Crm\Reservation\EventsHandler;

use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Event;
use CCrmSaleHelper;

/**
 * Event handlers related to the order payment.
 */
class Payment
{
	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public static function OnSalePaymentEntitySaved(Event $event)
	{
		$payment = $event->getParameter('ENTITY');
		if (($payment instanceof \Bitrix\Crm\Order\Payment) === false)
		{
			return;
		}

		self::reservePaidProducts($payment);
	}

	/**
	 * Reservation products of paid order.
	 *
	 * @param \Bitrix\Crm\Order\Payment $payment
	 *
	 * @return void
	 */
	private static function reservePaidProducts(\Bitrix\Crm\Order\Payment $payment)
	{
		$isNew = $payment->getFields()->isChanged('ID');
		$isChangePaid = $payment->getFields()->isChanged('PAID');
		if (!$isChangePaid || $isNew)
		{
			return;
		}

		if ($payment->isPaid())
		{
			ReservationService::getInstance()->reservationProductsByPayment($payment);
		}
		else
		{
			ReservationService::getInstance()->removeReservesProductsByPayment($payment);
		}
	}
}

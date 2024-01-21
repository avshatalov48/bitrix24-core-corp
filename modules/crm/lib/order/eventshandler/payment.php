<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm\Order;

Main\Loader::includeModule('sale');

final class Payment
{
	private static bool $isExecutionEnabled = true;

	public static function OnBeforeSalePaymentEntitySaved(Main\Event $event)
	{
		$payment = $event->getParameter('ENTITY');

		if (
			!$payment instanceof Order\Payment
			|| !self::needExecuteEvent($payment)
		)
		{
			return;
		}

		$payableItemCollection = $payment->getPayableItemCollection();

		/** @var Order\BasketItem $basketItem */
		foreach ($payment->getOrder()->getBasket() as $basketItem)
		{
			$item = $payableItemCollection->createItemByBasketItem($basketItem);
			$item->setField('QUANTITY', $basketItem->getQuantity());
		}

		$collection = $payment->getOrder()->getShipmentCollection()->getNotSystemItems();

		/** @var Order\Shipment $shipment */
		foreach ($collection as $shipment)
		{
			$item = $payableItemCollection->createItemByShipment($shipment);
			$item->setField('QUANTITY', 1);
		}
	}

	private static function needExecuteEvent(Order\Payment $payment) : bool
	{
		return
			$payment->getId() === 0
			&& $payment->getPayableItemCollection()->isEmpty()
			&& self::isExecutionEnabled()
		;
	}

	public static function isExecutionEnabled() : bool
	{
		return self::$isExecutionEnabled === true;
	}

	public static function disableExecution() : void
	{
		self::$isExecutionEnabled = false;
	}

	public static function enableExecution() : void
	{
		self::$isExecutionEnabled = true;
	}
}
<?php

namespace Bitrix\Crm\Terminal\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Terminal\PaymentHelper;

class Payment
{
	private static ?bool $isAdd = null;

	public static function onBeforeSalePaymentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');
		if (!PaymentHelper::isPayment($payment))
		{
			return;
		}

		self::$isAdd = $payment->getId() <= 0;
	}

	public static function onSalePaymentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');
		if (!PaymentHelper::isPayment($payment) || $payment->getId() <= 0)
		{
			return;
		}

		if (PaymentHelper::isTerminalPayment($payment))
		{
			if (self::$isAdd)
			{
				Crm\Terminal\PullManager::add([$payment->getId()]);
			}
			else
			{
				Crm\Terminal\PullManager::update([$payment->getId()]);
			}
		}
	}

	public static function onSalePaymentEntityDeleted(Main\Event $event): Main\EventResult
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');
		if (!PaymentHelper::isPayment($payment) || $payment->getId() <= 0)
		{
			return new Main\EventResult(Main\EventResult::SUCCESS);
		}

		if (PaymentHelper::isTerminalPayment($payment))
		{
			Crm\Terminal\PullManager::delete([$payment->getId()]);
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}
}
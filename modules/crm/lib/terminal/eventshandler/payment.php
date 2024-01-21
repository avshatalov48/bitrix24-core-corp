<?php

namespace Bitrix\Crm\Terminal\EventsHandler;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm;

class Payment
{
	private static ?bool $isNew = null;

	public static function onBeforeSalePaymentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');

		if (!$payment instanceof Crm\Order\Payment)
		{
			return;
		}

		self::$isNew = $payment->getId() === 0;
	}

	public static function onSalePaymentEntitySaved(Main\Event $event): void
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');

		if (
			$payment instanceof Crm\Order\Payment
			&& $payment->getId() > 0
			&& self::$isNew === false
			&& Container::getInstance()->getTerminalPaymentService()->isTerminalPayment($payment->getId())
		)
		{
			Crm\Terminal\PullManager::update([$payment->getId()]);
		}
	}

	public static function onSalePaymentEntityDeleted(Main\Event $event): Main\EventResult
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');

		$terminalPaymentService = Container::getInstance()->getTerminalPaymentService();

		if (
			$payment instanceof Crm\Order\Payment
			&& $payment->getId() > 0
			&& $terminalPaymentService->isTerminalPayment($payment->getId())
		)
		{
			$terminalPaymentService->unmarkPayment($payment->getId());
			Crm\Terminal\PullManager::delete([$payment->getId()]);
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}
}

<?php

namespace Bitrix\Crm\Terminal\EventsHandler;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

class OnSalePsBeforeInitiatePay
{
	public static function handle(Main\Event $event): Main\EventResult
	{
		$terminalPaymentService = Container::getInstance()->getTerminalPaymentService();

		/** @var \Bitrix\Sale\Payment $payment */
		$payment = $event->getParameter('payment');

		/** @var \Bitrix\Sale\PaySystem\Service $service */
		$service = $event->getParameter('service');

		if (
			$service->isFiscalizationAware()
			&& $payment instanceof Crm\Order\Payment
			&& $terminalPaymentService->isTerminalPayment($payment->getId())
			&& $terminalPaymentService->isPaymentWithoutProducts($payment)
		)
		{
			$isFiscalizationEnabled = $service->isFiscalizationEnabled($payment);
			if ($isFiscalizationEnabled)
			{
				return new Main\EventResult(
					Main\EventResult::ERROR,
					[
						'ERROR' => new Main\Error(
							Loc::getMessage('CRM_TERMINAL_EVENTS_HANDLER_FISCALIZATION_ERROR'),
							'fiscalization_enabled'
						)
					]
				);
			}
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}
}

<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Crm;
use Bitrix\Sale;

class PaymentHelper
{
	public static function isPayment($payment): bool
	{
		return $payment instanceof Crm\Order\Payment;
	}

	public static function isTerminalPayment(Sale\Payment $payment): bool
	{
		$order = $payment->getOrder();
		$collection = $order->getTradeBindingCollection();

		/** @var Crm\Order\TradeBindingEntity $binding */
		foreach ($collection as $binding)
		{
			$platform = $binding->getTradePlatform();
			if (
				$platform
				&& $platform->getCode() === Crm\Order\TradingPlatform\Terminal::TRADING_PLATFORM_CODE
			)
			{
				return true;
			}
		}

		return false;
	}
}
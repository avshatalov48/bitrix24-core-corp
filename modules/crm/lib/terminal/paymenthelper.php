<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Crm;

class PaymentHelper
{
	public static function isPayment($payment): bool
	{
		return $payment instanceof Crm\Order\Payment;
	}
}

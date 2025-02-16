<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Booking;

use Bitrix\Booking\Exception\Exception;

class CancelBookingException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Cancel failed' : $message;
		$code = self::CODE_BOOKING_CANCEL_FAILED;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

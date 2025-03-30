<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Booking;

use Bitrix\Booking\Internals\Exception\Exception;

class ConfirmBookingException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Confirmation failed' : $message;
		$code = self::CODE_BOOKING_CONFIRMATION_FAILED;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

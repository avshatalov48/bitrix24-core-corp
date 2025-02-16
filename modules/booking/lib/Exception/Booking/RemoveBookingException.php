<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Booking;

use Bitrix\Booking\Exception\Exception;

class RemoveBookingException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing booking' : $message;
		$code = self::CODE_BOOKING_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

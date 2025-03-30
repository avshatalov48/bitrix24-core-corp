<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Booking;

use Bitrix\Booking\Internals\Exception\Exception;

class BookingNotFoundException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Booking not found' : $message;
		$code = self::CODE_BOOKING_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

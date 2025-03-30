<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Booking;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateBookingException extends Exception
{
	public function __construct($message = '', int $code = 0)
	{
		$message = $message === '' ? 'Failed creating new booking' : $message;
		$code = $code === 0 ? self::CODE_BOOKING_CREATE : $code;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

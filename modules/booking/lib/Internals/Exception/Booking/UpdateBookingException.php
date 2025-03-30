<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Booking;

use Bitrix\Booking\Internals\Exception\Exception;

class UpdateBookingException extends Exception
{
	public function __construct($message = '', int $code = 0)
	{
		$message = $message === '' ? 'Failed updating booking' : $message;
		$code = $code === 0 ? self::CODE_BOOKING_UPDATE : $code;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

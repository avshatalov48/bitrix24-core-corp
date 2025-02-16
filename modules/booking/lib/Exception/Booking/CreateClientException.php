<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Booking;

use Bitrix\Booking\Exception\Exception;

class CreateClientException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new client' : $message;
		$code = self::CODE_BOOKING_CLIENT_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

class OptionSetException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed setting value to the options storage' : $message;
		$code = self::CODE_BOOKING_OPTION_SET_FAILED;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

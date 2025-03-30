<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

class InvalidArgumentException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Invalid argument provided' : $message;
		$code = self::CODE_INVALID_ARGUMENT;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

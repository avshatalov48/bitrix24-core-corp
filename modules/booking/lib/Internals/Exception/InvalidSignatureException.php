<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

class InvalidSignatureException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Invalid signature or expired token' : $message;
		$code = self::CODE_INVALID_SIGNATURE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

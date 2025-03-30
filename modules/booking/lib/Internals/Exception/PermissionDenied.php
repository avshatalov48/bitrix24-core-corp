<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

class PermissionDenied extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Permission denied' : $message;
		$code = self::CODE_PERMISSION_DENIED;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

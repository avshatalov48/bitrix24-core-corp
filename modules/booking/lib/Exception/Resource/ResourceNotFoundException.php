<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Resource;

use Bitrix\Booking\Exception\Exception;

class ResourceNotFoundException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Resource not found' : $message;
		$code = self::CODE_RESOURCE_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

<?php

namespace Bitrix\Booking\Internals\Exception\Resource;

use Bitrix\Booking\Internals\Exception\Exception;

class ExternalResourceException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed processing external resource' : $message;
		$code = self::CODE_EXTERNAL_RESOURCE_PROCESSING;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

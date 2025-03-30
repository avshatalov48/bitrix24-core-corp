<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Resource;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateResourceException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new resource' : $message;
		$code = self::CODE_RESOURCE_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

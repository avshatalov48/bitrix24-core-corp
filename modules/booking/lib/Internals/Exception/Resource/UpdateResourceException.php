<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Resource;

use Bitrix\Booking\Internals\Exception\Exception;

class UpdateResourceException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed updating resource' : $message;
		$code = self::CODE_RESOURCE_UPDATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

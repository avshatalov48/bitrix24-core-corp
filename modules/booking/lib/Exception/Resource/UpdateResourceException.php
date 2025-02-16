<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Resource;

use Bitrix\Booking\Exception\Exception;

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

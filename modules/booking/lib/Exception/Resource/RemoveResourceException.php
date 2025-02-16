<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Resource;

use Bitrix\Booking\Exception\Exception;

class RemoveResourceException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing resource' : $message;
		$code = self::CODE_RESOURCE_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\ResourceType;

use Bitrix\Booking\Internals\Exception\Exception;

class RemoveResourceTypeException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing resource type' : $message;
		$code = self::CODE_RESOURCE_TYPE_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

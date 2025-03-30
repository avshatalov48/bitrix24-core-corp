<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\ResourceType;

use Bitrix\Booking\Internals\Exception\Exception;

class UpdateResourceTypeException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed updating resource type' : $message;
		$code = self::CODE_RESOURCE_TYPE_UPDATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

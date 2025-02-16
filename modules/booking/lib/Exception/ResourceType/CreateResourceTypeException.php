<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\ResourceType;

use Bitrix\Booking\Exception\Exception;

class CreateResourceTypeException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new resource type' : $message;
		$code = self::CODE_RESOURCE_TYPE_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

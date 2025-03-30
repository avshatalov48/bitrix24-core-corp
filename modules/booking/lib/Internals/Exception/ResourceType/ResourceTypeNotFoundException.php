<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\ResourceType;

use Bitrix\Booking\Internals\Exception\Exception;

class ResourceTypeNotFoundException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Resource type not found' : $message;
		$code = self::CODE_RESOURCE_TYPE_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

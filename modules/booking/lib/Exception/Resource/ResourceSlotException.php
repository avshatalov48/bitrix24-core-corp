<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Resource;

use Bitrix\Booking\Exception\Exception;

class ResourceSlotException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Resource slot error' : $message;
		$code = self::CODE_RESOURCE_SLOT;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

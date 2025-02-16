<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Counter;

use Bitrix\Booking\Exception\Exception;

class UpdateCounterException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Updating counter failed' : $message;
		$code = self::CODE_COUNTER_UPDATE_FAILED;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

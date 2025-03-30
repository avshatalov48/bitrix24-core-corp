<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

use Bitrix\Main\Error;

class ErrorBuilder
{
	public static function buildFromException(Exception $exception): Error
	{
		return new Error(
			$exception->getMessage(),
			$exception->getCode(),
			[
				'isPublic' => $exception->isPublic(),
			]
		);
	}

	public static function build(string $message, int|string $code = 0, bool $isPublic = false): Error
	{
		return new Error(
			$message,
			$code,
			[
				'isPublic' => $isPublic,
			]
		);
	}
}

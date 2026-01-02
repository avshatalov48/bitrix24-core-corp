<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class InternalErrorException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Internal error' : $message;
		$code = self::CODE_YANDEX_INTERNAL_ERROR;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_INTERNAL_ERROR';
	}
}

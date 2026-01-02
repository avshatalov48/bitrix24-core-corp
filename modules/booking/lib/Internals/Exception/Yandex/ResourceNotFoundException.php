<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class ResourceNotFoundException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Resource not found' : $message;
		$code = self::CODE_YANDEX_RESOURCE_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_RESOURCE_NOT_FOUND';
	}
}

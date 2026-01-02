<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class ServiceNotFoundException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Service not found' : $message;
		$code = self::CODE_YANDEX_SERVICE_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_SERVICE_NOT_FOUND';
	}
}

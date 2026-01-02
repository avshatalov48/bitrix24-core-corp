<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class BookingCreateForbiddenException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Booking create forbidden' : $message;
		$code = self::CODE_YANDEX_BOOKING_CREATE_FORBIDDEN;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_CREATE_FORBIDDEN';
	}
}

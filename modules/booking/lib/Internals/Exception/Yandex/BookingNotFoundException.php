<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class BookingNotFoundException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Booking not found' : $message;
		$code = self::CODE_YANDEX_BOOKING_NOT_FOUND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_BOOKING_NOT_FOUND';
	}
}

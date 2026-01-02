<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class BookingCancelForbidden extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Booking cancel forbidden' : $message;
		$code = self::CODE_YANDEX_BOOKING_CANCEL_FORBIDDEN;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_CANCEL_FORBIDDEN';
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Yandex;

class SlotUnavailableException extends YandexException
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Slot Unavailable' : $message;
		$code = self::CODE_YANDEX_SLOT_UNAVAILABLE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}

	public function getExternalCode(): string
	{
		return 'YANDEX_SLOT_UNAVAILABLE';
	}
}

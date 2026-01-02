<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Booking;

class CancelBookingResponse implements \JsonSerializable
{
	public function jsonSerialize(): array
	{
		return [
			'cancelled' => true,
		];
	}
}

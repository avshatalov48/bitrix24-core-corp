<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Booking;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item\Booking;

class GetBookingResponse implements \JsonSerializable
{
	public function __construct(
		public readonly Booking $booking,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'booking' => $this->booking->toArray(),
		];
	}
}

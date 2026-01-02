<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

class CalendarGetBookingsDatesCountResponse implements \JsonSerializable
{
	public function __construct(
		public readonly int $count,
		public readonly string|null $minDate,
		public readonly string|null $maxDate,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'count' => $this->count,
			'minDate' => $this->minDate,
			'maxDate' => $this->maxDate,
		];
	}
}

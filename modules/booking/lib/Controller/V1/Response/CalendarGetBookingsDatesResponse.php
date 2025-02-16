<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

use Bitrix\Booking\Entity\DateTimeCollection;

class CalendarGetBookingsDatesResponse implements \JsonSerializable
{
	public function __construct(
		public readonly DateTimeCollection $foundDates,
		public readonly DateTimeCollection $foundDatesWithCounters,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'foundDates' => array_map(
				static fn(\DateTimeImmutable $date) => $date->format('Y-m-d'),
				iterator_to_array($this->foundDates),
			),
			'foundDatesWithCounters' => array_map(
				static fn(\DateTimeImmutable $date) => $date->format('Y-m-d'),
				iterator_to_array($this->foundDatesWithCounters),
			),
		];
	}
}

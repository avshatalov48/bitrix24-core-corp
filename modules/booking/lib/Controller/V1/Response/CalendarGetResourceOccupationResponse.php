<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

use Bitrix\Booking\Entity\DateTimeCollection;

class CalendarGetResourceOccupationResponse implements \JsonSerializable
{
	public function __construct(
		public readonly DateTimeCollection $freeDates
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'freeDates' => array_map(
				static fn(\DateTimeImmutable $date) => $date->format('Y-m-d'),
				iterator_to_array($this->freeDates)
			),
		];
	}
}

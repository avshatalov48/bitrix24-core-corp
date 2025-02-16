<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use DateTimeImmutable;

class MultiResourceEachDayFirstOccurrenceRequest
{
	public function __construct(
		public readonly array $resourceCollections,
		public readonly BookingCollection $eventCollection,
		public readonly DateTimeCollection $searchDates,
		public readonly int|null $sizeInMinutes = null
	)
	{
	}
}

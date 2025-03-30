<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DateTimeCollection;

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

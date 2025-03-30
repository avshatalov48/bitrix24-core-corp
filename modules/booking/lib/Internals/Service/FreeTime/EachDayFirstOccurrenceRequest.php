<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;

class EachDayFirstOccurrenceRequest
{
	public function __construct(
		public readonly ResourceCollection $resourceCollection,
		public readonly BookingCollection $eventCollection,
		public readonly DateTimeCollection $searchDates,
		public readonly int|null $sizeInMinutes = null
	)
	{
	}
}

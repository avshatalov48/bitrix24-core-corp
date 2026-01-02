<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Slot\RangeCollection;

class NearestDateSlotsRequest
{
	public function __construct(
		public readonly RangeCollection $slotRanges,
		public readonly BookingCollection $eventCollection,
		public readonly DatePeriod $searchPeriod
	)
	{
	}
}

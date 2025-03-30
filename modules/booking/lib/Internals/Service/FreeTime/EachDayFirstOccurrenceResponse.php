<?php

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\DatePeriodCollection;
use Bitrix\Booking\Entity\DateTimeCollection;

class EachDayFirstOccurrenceResponse
{
	public function __construct(
		public DateTimeCollection $foundDates,
		public DatePeriodCollection $foundPeriods,
	)
	{
	}
}

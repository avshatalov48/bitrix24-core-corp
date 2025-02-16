<?php

namespace Bitrix\Booking\Internals\Query\FreeTime;

use Bitrix\Booking\Entity\DateTimeCollection;

class MultiResourceEachDayFirstOccurrenceResponse
{
	public function __construct(
		public DateTimeCollection $foundDates
	)
	{
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\DateTimeCollection;

class MultiResourceEachDayFirstOccurrenceResponse
{
	public function __construct(
		public DateTimeCollection $foundDates
	)
	{
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\EventIntersection;

use Bitrix\Booking\Entity\DatePeriod;

class DatePeriodWithDatePeriod
{
	public function doIntersect(
		DatePeriod $datePeriod1,
		DatePeriod $datePeriod2
	): bool
	{
		return $datePeriod1->intersects($datePeriod2);
	}
}

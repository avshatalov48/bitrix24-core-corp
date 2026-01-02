<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Calendar;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Main\Type\DateTime;

class IcsBuilderParams
{
	public function __construct(
		public readonly DatePeriod $datePeriod,
		public readonly string $name,
		public readonly string $description,
		public readonly DateTime $currentDate,
		public readonly string $uid,
		public readonly array $reminders = [],
		public readonly Rrule|null $rrule = null,
	)
	{
	}
}

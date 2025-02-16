<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Favorites;

use Bitrix\Booking\Entity\DatePeriod;

class GetListRequest
{
	public function __construct(
		public readonly int $managerId,
		public readonly DatePeriod|null $datePeriod = null,
		public readonly bool $withCounters = false,
	)
	{
	}
}

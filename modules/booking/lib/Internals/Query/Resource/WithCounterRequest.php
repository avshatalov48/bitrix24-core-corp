<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\ResourceCollection;

class WithCounterRequest
{
	public function __construct(
		private readonly ResourceCollection $resourceCollection,
		public readonly int $userId,
		private readonly ?DatePeriod $datePeriod = null,
	)
	{
	}

	public function getResourceCollection(): ResourceCollection
	{
		return $this->resourceCollection;
	}

	public function getDatePeriod(): DatePeriod
	{
		if ($this->datePeriod)
		{
			return $this->datePeriod;
		}

		// default is today
		return new DatePeriod(
			dateFrom: new \DateTimeImmutable("today 00:00"),
			dateTo: new \DateTimeImmutable("today 24:00"),
		);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;

class FirstOccurrenceRequest
{
	public int $returnCnt = 1;

	public function __construct(
		public readonly RangeCollection $slotRanges,
		public readonly BookingCollection $bookingCollection,
		public readonly DatePeriod $searchPeriod,
		public readonly int|null $sizeInMinutes = null
	)
	{
	}

	public function setReturnCnt(int $cnt): self
	{
		if ($this->returnCnt <= 0)
		{
			throw new InvalidArgumentException('Should be greater than 0');
		}

		$this->returnCnt = $cnt;

		return $this;
	}
}

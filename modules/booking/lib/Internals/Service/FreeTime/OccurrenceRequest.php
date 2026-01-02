<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Slot\RangeCollection;

class OccurrenceRequest
{
	public int|null $returnCnt = null;
	public int|null $sizeInMinutes = null;

	public function __construct(
		public readonly RangeCollection $slotRanges,
		public readonly BookingCollection $bookingCollection,
		public readonly DatePeriod $searchPeriod,
		public readonly int $stepSize,
	)
	{
	}

	public function setReturnCnt(int|null $cnt): self
	{
		$this->returnCnt = $cnt;

		return $this;
	}

	public function setSizeInMinutes(int|null $sizeInMinutes): self
	{
		$this->sizeInMinutes = $sizeInMinutes;

		return $this;
	}
}

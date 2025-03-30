<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DatePeriodCollection;
use Bitrix\Booking\Entity\Slot\Range;
use DateInterval;

class FirstOccurrenceHandler
{
	public function __invoke(FirstOccurrenceRequest $request): DatePeriodCollection
	{
		$slotRanges = $request->slotRanges;
		$bookingCollection = $request->bookingCollection;
		$searchPeriod = $request->searchPeriod;
		$sizeInMinutes = $request->sizeInMinutes;
		$returnCnt = $request->returnCnt;

		$result = new DatePeriodCollection();

		if ($slotRanges->isEmpty())
		{
			return $result;
		}

		$searchDates = $searchPeriod->getDateTimeCollection();
		if ($searchDates->isEmpty())
		{
			return $result;
		}

		foreach ($searchDates as $searchDate)
		{
			/** @var Range $slotRange */
			foreach ($slotRanges as $slotRange)
			{
				if (!in_array($searchDate->format('D'), $slotRange->getWeekDays(), true))
				{
					continue;
				}

				$slotSize = $slotRange->getSlotSize();
				$periodSize =
					$sizeInMinutes === null
						? $slotSize
						: $slotRange->getSlotsRequiredByMinutes($sizeInMinutes) * $slotSize;

				$slotRangeDatePeriod = $slotRange->makeDatePeriod($searchDate);

				$slotRangeEvents = $bookingCollection->filterByDatePeriod(
					$slotRangeDatePeriod
				);

				$currentDatePeriod = new DatePeriod(
					$slotRangeDatePeriod->getDateFrom(),
					$slotRangeDatePeriod->getDateFrom()->add(
						new DateInterval('PT' . $periodSize . 'M')
					)
				);

				while ($slotRangeDatePeriod->contains($currentDatePeriod))
				{
					if ($slotRangeEvents->isEmpty())
					{
						$result->add($currentDatePeriod);
						if ($result->count() >= $returnCnt)
						{
							return $result;
						}

						$currentDatePeriod = $currentDatePeriod->addMinutes($slotSize);
						continue;
					}

					$intersects = false;
					/** @var Booking $slotRangeEvent */
					foreach ($slotRangeEvents as $slotRangeEvent)
					{
						if ($slotRangeEvent->doEventsIntersect($currentDatePeriod))
						{
							$intersects = true;
							break;
						}
					}

					if (!$intersects)
					{
						$result->add($currentDatePeriod);
						if ($result->count() >= $returnCnt)
						{
							return $result;
						}
					}

					$currentDatePeriod = $currentDatePeriod->addMinutes($slotSize);
				}
			}
		}

		return $result;
	}
}

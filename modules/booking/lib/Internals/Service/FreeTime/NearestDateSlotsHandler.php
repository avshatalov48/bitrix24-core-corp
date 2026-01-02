<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DatePeriodCollection;
use DateInterval;

class NearestDateSlotsHandler
{
	private const STEP_SIZE = 5;

	public function __invoke(NearestDateSlotsRequest $request): DatePeriodCollection
	{
		$result = new DatePeriodCollection();

		if ($request->slotRanges->isEmpty())
		{
			return $result;
		}

		$searchDates = $request->searchPeriod->getDateTimeCollection();
		if ($searchDates->isEmpty())
		{
			return $result;
		}

		foreach ($searchDates as $searchDate)
		{
			foreach ($request->slotRanges as $slotRange)
			{
				if (!in_array($searchDate->format('D'), $slotRange->getWeekDays(), true))
				{
					continue;
				}

				$slotSize = $slotRange->getSlotSize();
				$slotRangeDatePeriod = $slotRange->makeDatePeriod($searchDate);
				$slotRangeEvents = $request->eventCollection->filterByDatePeriod($slotRangeDatePeriod);

				$currentDatePeriod = new DatePeriod(
					$slotRangeDatePeriod->getDateFrom(),
					$slotRangeDatePeriod->getDateFrom()->add(
						new DateInterval('PT' . $slotSize . 'M')
					)
				);

				while ($slotRangeDatePeriod->contains($currentDatePeriod))
				{
					// skip past slots
					if ($currentDatePeriod->getDateFrom()->getTimestamp() < time())
					{
						$currentDatePeriod = $currentDatePeriod->addMinutes(self::STEP_SIZE);

						continue;
					}

					if ($slotRangeEvents->isEmpty())
					{
						$result->add($currentDatePeriod);
						$currentDatePeriod = $currentDatePeriod->addMinutes($slotSize);

						continue;
					}

					$intersects = false;
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
						$currentDatePeriod = $currentDatePeriod->addMinutes($slotSize);

						continue;
					}

					$currentDatePeriod = $currentDatePeriod->addMinutes(self::STEP_SIZE);
				}
			}

			if (!$result->isEmpty())
			{
				return $result;
			}
		}

		return $result;
	}
}

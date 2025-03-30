<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DatePeriodCollection;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\EventInterface;
use Bitrix\Booking\Entity\Slot\Range;
use DateInterval;

class EachDayFirstOccurrenceHandler
{
	private const MIN_STEP_SIZE = 15;

	public function __invoke(EachDayFirstOccurrenceRequest $request): EachDayFirstOccurrenceResponse
	{
		$response = new EachDayFirstOccurrenceResponse(
			foundDates: new DateTimeCollection(),
			foundPeriods: new DatePeriodCollection(),
		);

		if (
			$request->resourceCollection->isEmpty()
			|| $request->searchDates->isEmpty()
		)
		{
			return $response;
		}

		foreach ($request->searchDates as $searchDate)
		{
			$slotRanges = $request->resourceCollection->mergeSlotRanges($searchDate);
			if ($slotRanges->isEmpty())
			{
				continue;
			}

			/** @var Range $slotRange */
			foreach ($slotRanges as $slotRange)
			{
				if (!in_array($searchDate->format('D'), $slotRange->getWeekDays(), true))
				{
					continue;
				}

				$slotSize = $slotRange->getSlotSize();
				$stepSize = self::getStepSize($slotSize);
				$slotRangeDatePeriod = $slotRange->makeDatePeriod($searchDate);
				$slotRangeEvents = $this->sortEventCollection(
					$request->eventCollection->filterByDatePeriod($slotRangeDatePeriod)
				);
				$periodSize =
					$request->sizeInMinutes === null
						? $slotSize
						: $slotRange->getSlotsRequiredByMinutes($request->sizeInMinutes) * $slotSize
				;
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
						$response->foundDates->add($searchDate);
						$response->foundPeriods->add($currentDatePeriod);

						break 2;
					}

					$intersects = false;
					/** @var EventInterface $slotRangeEvent */
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
						$response->foundDates->add($searchDate);
						$response->foundPeriods->add($currentDatePeriod);

						break 2;
					}

					$currentDatePeriod = $currentDatePeriod->addMinutes($stepSize);
				}
			}
		}

		return $response;
	}

	private static function getStepSize(int $slotSize): int
	{
		/**
		 * For the best search accuracy $stepSize should always be equal to $slotSize
		 * but for performance reasons we have to specify minimum step size
		 */
		return max($slotSize, self::MIN_STEP_SIZE);
	}

	private function sortEventCollection(BookingCollection $eventCollection): BookingCollection
	{
		$priority1 = $priority2 = $priority3 = [];

		/** @var Booking $event */
		foreach ($eventCollection as $event)
		{
			if ($event->isEventRecurring())
			{
				$rrule = $event->getRrule();

				/**
				 * Those events that have interval greater than one are the slowest to check for intersections
				 */
				if (
					strpos($rrule, 'INTERVAL=') !== false
					&& strpos($rrule, 'INTERVAL=1') === false
				)
				{
					$priority3[] = $event;
				}
				else
				{
					$priority2[] = $event;
				}
			}
			else
			{
				/**
				 * Not recurring events can be checked faster for intersections
				 */
				$priority1[] = $event;
			}
		}

		return new BookingCollection(
			...$priority1,
			...$priority2,
			...$priority3
		);
	}
}

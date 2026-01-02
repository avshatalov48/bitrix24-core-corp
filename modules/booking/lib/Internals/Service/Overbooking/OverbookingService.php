<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Overbooking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;

class OverbookingService
{
	public function __construct(
		private readonly BookingRepositoryInterface $bookingRepository
	)
	{
	}

	/**
	 * Determines which bookings are no longer overbooked and which are newly overbooked
	 * based on the changes between old and new booking states.
	 */
	public function getIsOverbookingUpdates(
		Booking $oldBookingState,
		Booking $newBookingState,
		IntersectionResult $newBookingStateIntersections,
	): OverbookingChangesResult
	{
		$result = OverbookingChangesResult::buildEmpty();

		if (!$this->canOverbookingStatusChange($oldBookingState, $newBookingState))
		{
			return $result;
		}
		$oldIntersections = $this->bookingRepository->getIntersectionsList($oldBookingState);
		// some bookings which were overbooked by old state of current processed booking, may also be overbooked by other bookings,
		// in that case changes in current processed booking won't affect overbooking status of them
		$potentiallyAffectedBookings = $this->getPotentialIntersections($oldIntersections);
		$intersectionResult = $this->findBookingIntersections($potentiallyAffectedBookings);
		// collect only bookings, which lost overbooked status after current booking state change
		// if bookings stay overbooked after current booking state change, we don't need to process them
		$notOverbookedAnymore = $intersectionResult->nonIntersecting;
		foreach ($notOverbookedAnymore as $item)
		{
			$result->notOverbooked->addUnique($item);
		}

		// some bookings which were overbooked by new state of current processed booking, may also be overbooked by other bookings before
		// in that case changes in current processed booking won't affect overbooking status of them
		$potentiallyNewIntersections = $this->getPotentialIntersections(
			$newBookingStateIntersections->getBookingCollection(),
		);

		$newIntersectionsWithoutCurrent = new BookingCollection();
		foreach ($potentiallyNewIntersections as $booking)
		{
			if ($booking->getId() === $newBookingState->getId())
			{
				continue;
			}

			$newIntersectionsWithoutCurrent->addUnique($booking);
		}

		// collect bookings which not overbooked before current booking state changed
		$newIntersections = $this->findBookingIntersections($newIntersectionsWithoutCurrent);
		$notIntersect = $newIntersections->nonIntersecting;

		// check which of bookings state changed by new current processed booking state
		/** @var Booking $booking */
		foreach ($notIntersect as $booking)
		{
			if ($booking->getId() === $newBookingState->getId())
			{
				continue;
			}

			$isOverbookedEarly = !array_diff(
				$booking->getResourceCollection()->getEntityIds(),
				$oldBookingState->getResourceCollection()->getEntityIds()
			) && $booking->doEventsIntersect($oldBookingState);
			$isOverbookedNow = $booking->doEventsIntersect($newBookingState);

			if (!$isOverbookedEarly && $isOverbookedNow)
			{
				$result->overbooked->addUnique($booking);
			}
			else if ($isOverbookedEarly && !$isOverbookedNow)
			{
				$result->notOverbooked->addUnique($booking);
			}
		}

		return $result;
	}

	/**
	 * Checks if booking changes could potentially affect overbooking status
	 */
	private function canOverbookingStatusChange(
		Booking $oldBookingState,
		Booking $newBookingState,
	): bool
	{
		$datePeriodChanged = $oldBookingState->getDatePeriod()?->toArray() !== $newBookingState->getDatePeriod()?->toArray();
		$resourceChanged = $oldBookingState->getResourceCollection()->getEntityIds() !== $newBookingState->getResourceCollection()->getEntityIds();
		$rruleChanged = $oldBookingState->getRrule() !== $newBookingState->getRrule();

		return $datePeriodChanged
			|| $resourceChanged
			|| $rruleChanged
		;
	}

	/**
	 * Gets potentially intersecting bookings for the specified booking and existing intersections
	 */
	private function getPotentialIntersections(
		BookingCollection $intersections,
	): BookingCollection
	{
		$result = new BookingCollection();

		foreach ($intersections as $intersectionBooking)
		{
			$result->addUnique($intersectionBooking);

			$currentIntersections = $this->bookingRepository->getIntersectionsList($intersectionBooking);
			if ($currentIntersections->isEmpty())
			{
				continue;
			}

			foreach ($currentIntersections as $currentIntersection)
			{
				$result->addUnique($currentIntersection);
			}
		}

		return $result;
	}

	private function findBookingIntersections(BookingCollection $bookings): IntersectionStatusUpdateResult
	{
		$count = $bookings->count();
		if ($count <= 1)
		{
			return new IntersectionStatusUpdateResult(
				new BookingCollection(),
				$bookings,
			);
		}

		$intersect = [];
		/** @var Booking[] $bookingsArray */
		$bookingsArray = $bookings->getIterator()->getArrayCopy();

		usort(
			$bookingsArray,
			static fn (
				Booking $b1,
				Booking $b2,
			) => $b1->getDatePeriod()?->getDateFrom() <=> $b2->getDatePeriod()?->getDateFrom(),
		);

		for ($i = 0; $i < $count - 1; $i++)
		{
			$booking1 = $bookingsArray[$i];
			$id1 = $booking1->getId();

			for ($j = $i + 1; $j < $count; $j++)
			{
				$booking2 = $bookingsArray[$j];
				$id2 = $booking2->getId();
				if (isset($intersect[$id1], $intersect[$id2]))
				{
					continue;
				}

				if (!$booking1->doEventsIntersect($booking2))
				{
					continue;
				}

				$intersect[$id1] = $booking1;
				$intersect[$id2] = $booking2;
			}
		}

		$notIntersect = [];

		foreach ($bookingsArray as $booking)
		{
			if (!isset($intersect[$booking->getId()]))
			{
				$notIntersect[] = $booking;
			}
		}

		return new IntersectionStatusUpdateResult(
			new BookingCollection(...array_values($intersect)),
			new BookingCollection(...$notIntersect),
		);
	}
}

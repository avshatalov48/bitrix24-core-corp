<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\FreeTime;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;

class MultiResourceEachDayFirstOccurrenceHandler
{
	public function __invoke(MultiResourceEachDayFirstOccurrenceRequest $request): MultiResourceEachDayFirstOccurrenceResponse
	{
		if (
			empty($request->resourceCollections)
			|| $request->searchDates->isEmpty()
		)
		{
			return new MultiResourceEachDayFirstOccurrenceResponse(
				new DateTimeCollection(),
			);
		}

		$currentSearchDates = $request->searchDates;
		foreach ($request->resourceCollections as $resourceCollection)
		{
			$singleResourceCollectionQueryResponse = (new EachDayFirstOccurrenceHandler())(
				new EachDayFirstOccurrenceRequest(
					$resourceCollection,
					$this->getFilteredBookingCollection(
						$request->eventCollection,
						$resourceCollection
					),
					$currentSearchDates,
					$request->sizeInMinutes
				)
			);

			$currentSearchDates = $currentSearchDates->diff($singleResourceCollectionQueryResponse->foundDates);
			if ($currentSearchDates->isEmpty())
			{
				break;
			}
		}

		return new MultiResourceEachDayFirstOccurrenceResponse(
			foundDates: $request->searchDates->diff($currentSearchDates),
		);
	}

	private function getFilteredBookingCollection(
		BookingCollection $bookingCollection,
		ResourceCollection $resourceCollection
	): BookingCollection
	{
		$result = new BookingCollection();

		/** @var Booking $booking */
		foreach ($bookingCollection as $booking)
		{
			$hasSomeResources = !empty(
				array_intersect(
					$resourceCollection->getEntityIds(),
					$booking->getResourceCollection()->getEntityIds()
				)
			);

			if ($hasSomeResources)
			{
				$result->add($booking);
			}
		}

		return $result;
	}
}

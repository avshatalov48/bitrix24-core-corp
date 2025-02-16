<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use \Bitrix\Booking\Internals\Query\Booking;

class WithCounterHandler
{
	public function __invoke(WithCounterRequest $request): ResourceCollection
	{
		$resourceCollection = $request->getResourceCollection();

		if ($resourceCollection->isEmpty())
		{
			return $resourceCollection;
		}

		$bookingCollection = $this->getBookingCollection($request);

		if ($bookingCollection->isEmpty())
		{
			return $resourceCollection;
		}

		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			$resourceCounter = 0;

			/** @var \Bitrix\Booking\Entity\Booking\Booking $booking */
			foreach ($bookingCollection as $booking)
			{
				if (in_array($resource->getId(), $booking->getResourceCollection()->getEntityIds()))
				{
					$resourceCounter += $booking->getCounter();
				}
			}

			$resource->setCounter($resourceCounter);
		}

		return $resourceCollection;
	}

	private function getBookingCollection(WithCounterRequest $request): BookingCollection
	{
		$datePeriod = $request->getDatePeriod();
		$filter = new GetListFilter([
			'RESOURCE_ID' => $request->getResourceCollection()->getEntityIds(),
			'WITHIN' => [
				'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
				'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
			],
		]);

		$bookingCollection = Container::getBookingRepository()->getList(filter: $filter);

		$bookingsWithCountersRequest = new Booking\WithCounterRequest(
			$bookingCollection,
			$request->userId,
		);

		return (new Booking\WithCounterHandler())($bookingsWithCountersRequest);
	}
}

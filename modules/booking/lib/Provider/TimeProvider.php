<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DatePeriodCollection;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Service\FreeTime\EachDayFirstOccurrenceHandler;
use Bitrix\Booking\Internals\Service\FreeTime\EachDayFirstOccurrenceRequest;
use Bitrix\Booking\Internals\Service\FreeTime\FirstOccurrenceHandler;
use Bitrix\Booking\Internals\Service\FreeTime\FirstOccurrenceRequest;
use Bitrix\Booking\Internals\Service\FreeTime\MultiResourceEachDayFirstOccurrenceHandler;
use Bitrix\Booking\Internals\Service\FreeTime\MultiResourceEachDayFirstOccurrenceRequest;

class TimeProvider
{
	public function getEachDayFirstOccurrence(
		ResourceCollection $resourceCollection,
		BookingCollection $eventCollection,
		DateTimeCollection $searchDates,
		int|null $sizeInMinutes = null
	): array
	{
		$request = new EachDayFirstOccurrenceRequest(
			$resourceCollection,
			$eventCollection,
			$searchDates,
			$sizeInMinutes,
		);

		$response = (new EachDayFirstOccurrenceHandler())($request);

		return [
			'foundDates' => $response->foundDates,
			'foundPeriods' => $response->foundPeriods,
		];
	}

	public function getFirstOccurrence(
		RangeCollection $slotRanges,
		BookingCollection $bookingCollection,
		DatePeriod $searchPeriod,
		int|null $sizeInMinutes = null,
		int|null $maxOccurrence = null
	): DatePeriodCollection
	{
		$request = new FirstOccurrenceRequest(
			$slotRanges,
			$bookingCollection,
			$searchPeriod,
			$sizeInMinutes,
		);

		if ($maxOccurrence !== null)
		{
			$request->setReturnCnt($maxOccurrence);
		}

		return (new FirstOccurrenceHandler())($request);
	}

	public function getMultiResourceEachDayFirstOccurrence(
		array $resourceCollections,
		BookingCollection $eventCollection,
		DateTimeCollection $searchDates,
		int|null $sizeInMinutes = null
	): array
	{
		$request = new MultiResourceEachDayFirstOccurrenceRequest(
			$resourceCollections,
			$eventCollection,
			$searchDates,
			$sizeInMinutes,
		);

		$response = (new MultiResourceEachDayFirstOccurrenceHandler())($request);

		return [
			'foundDates' => $response->foundDates,
		];
	}
}

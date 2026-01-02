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
use Bitrix\Booking\Internals\Service\FreeTime\OccurrenceHandler;
use Bitrix\Booking\Internals\Service\FreeTime\OccurrenceRequest;
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

	public function getOccurrences(
		RangeCollection $slotRanges,
		BookingCollection $bookingCollection,
		DatePeriod $searchPeriod,
		int $stepSize,
		int|null $sizeInMinutes = null,
		int|null $returnCnt = null,
	): DatePeriodCollection
	{
		return (new OccurrenceHandler())(
			(new OccurrenceRequest(
				slotRanges: $slotRanges,
				bookingCollection: $bookingCollection,
				searchPeriod: $searchPeriod,
				stepSize: $stepSize,
			))
				->setSizeInMinutes($sizeInMinutes)
				->setReturnCnt($returnCnt)
		);
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

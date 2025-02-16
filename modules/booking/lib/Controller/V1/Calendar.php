<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\CalendarGetResourceOccupationResponse;
use Bitrix\Booking\Controller\V1\Response\CalendarGetBookingsDatesResponse;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Query\FreeTime\MultiResourceEachDayFirstOccurrenceHandler;
use Bitrix\Booking\Internals\Query\FreeTime\MultiResourceEachDayFirstOccurrenceRequest;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity;
use Bitrix\Main\Request;
use DateTimeImmutable;
use DateInterval;
use DateTimeZone;

class Calendar extends BaseController
{
	private BookingProvider $bookingProvider;
	private ResourceProvider $resourceProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingProvider = new BookingProvider();
		$this->resourceProvider = new ResourceProvider();
	}

	public function getResourceOccupationAction(
		string $timezone,
		int $dateFromTs,
		int $dateToTs,
		array $resources,
	): CalendarGetResourceOccupationResponse
	{
		$userId = (int)CurrentUser::get()->getId();
		$dateFrom = (new DateTimeImmutable('@' . $dateFromTs))->setTimezone(new DateTimeZone($timezone));
		$dateTo = (new DateTimeImmutable('@' . $dateToTs))->setTimezone(new DateTimeZone($timezone));

		$resourceCollections = [];
		foreach ($resources as $resourceIds)
		{
			$resourceCollections[] = $this->resourceProvider->getList(
				$userId,
				null,
				null,
				['ID' => $resourceIds]
			);
		}

		$multiResourceEachDayFirstOccurrenceResponse = (new MultiResourceEachDayFirstOccurrenceHandler())(
			new MultiResourceEachDayFirstOccurrenceRequest(
				resourceCollections: $resourceCollections,
				eventCollection:
					(empty($resourceIds))
						? new Entity\Booking\BookingCollection()
						: $this->bookingProvider->getList(
							userId: $userId,
							filter: [
								'RESOURCE_ID' => $this->getAllResourceIds($resourceCollections),
								'WITHIN' => [
									'DATE_FROM' => $dateFrom->getTimestamp(),
									'DATE_TO' => $dateTo->getTimestamp(),
								],
							],
							select: [
								'RESOURCES',
							],
						),
				searchDates: (new DatePeriod($dateFrom, $dateTo))->getDateTimeCollection(),
			),
		);

		return new CalendarGetResourceOccupationResponse(
			freeDates: $multiResourceEachDayFirstOccurrenceResponse->foundDates,
		);
	}

	public function getBookingsDatesAction(
		string $timezone,
		int $dateFromTs,
		int $dateToTs,
		array $filter = [],
	): CalendarGetBookingsDatesResponse
	{
		return new CalendarGetBookingsDatesResponse(
			foundDates: $this->getBookingsDates(
				$timezone,
				$dateFromTs,
				$dateToTs,
				$filter
			),
			foundDatesWithCounters: $this->getBookingsDates(
				$timezone,
				$dateFromTs,
				$dateToTs,
				array_merge(
					$filter,
					[
						'HAS_COUNTERS_USER_ID' => (int)CurrentUser::get()->getId(),
					]
				)
			),
		);
	}

	private function getBookingsDates(
		string $timezone,
		int $dateFromTs,
		int $dateToTs,
		array $filter = [],
	): DateTimeCollection
	{
		$dateFrom = (new DateTimeImmutable('@' . $dateFromTs))->setTimezone(new DateTimeZone($timezone));
		$dateTo = (new DateTimeImmutable('@' . $dateToTs))->setTimezone(new DateTimeZone($timezone));

		$bookingsCollection = $this->bookingProvider->getList(
			userId: (int)CurrentUser::get()->getId(),
			filter: array_merge(
				$filter,
				[
					'WITHIN' => [
						'DATE_FROM' => $dateFrom->getTimestamp(),
						'DATE_TO' => $dateTo->getTimestamp(),
					],
				]
			),
		);

		$foundDates = new DateTimeCollection();
		$searchDates = (new DatePeriod($dateFrom, $dateTo))->getDateTimeCollection();
		foreach ($searchDates as $searchDate)
		{
			foreach ($bookingsCollection as $booking)
			{
				$dayDateFrom = DateTimeImmutable::createFromFormat(
					'Y-m-d H:i:s',
					$searchDate->format('Y-m-d') . ' 00:00:00'
				);
				$dayDateTo = $dayDateFrom->add(new DateInterval('P1D'));

				if ($booking->doEventsIntersect(new DatePeriod($dayDateFrom, $dayDateTo)))
				{
					$foundDates->add($searchDate);

					break;
				}
			}
		}

		return $foundDates;
	}

	/**
	 * @param ResourceCollection[] $resourceCollections
	 * @return array
	 */
	private function getAllResourceIds(array $resourceCollections): array
	{
		$result = [];

		foreach ($resourceCollections as $resourceCollection)
		{
			$result = array_merge(
				$result,
				$resourceCollection->getEntityIds()
			);
		}

		return array_unique($result);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\CalendarGetBookingsDatesCountResponse;
use Bitrix\Booking\Controller\V1\Response\CalendarGetResourceOccupationResponse;
use Bitrix\Booking\Controller\V1\Response\CalendarGetBookingsDatesResponse;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\DateTimeCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Booking\Provider\TimeProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity;
use Bitrix\Main\Request;
use DateTimeImmutable;
use DateInterval;
use DateTimeZone;
use Bitrix\Main\ORM\Fields\ExpressionField;

class Calendar extends BaseController
{
	private BookingProvider $bookingProvider;
	private BookingRepositoryInterface $bookingRepository;
	private ResourceProvider $resourceProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingProvider = new BookingProvider();
		$this->bookingRepository = Container::getBookingRepository();
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
				gridParams: new GridParams(
					filter: new ResourceFilter(['ID' => $resourceIds],
					),
				),
				userId: $userId,
			);
		}

		$multiResourceEachDayFirstOccurrenceResponse = (new TimeProvider())
			->getMultiResourceEachDayFirstOccurrence(
				resourceCollections: $resourceCollections,
				eventCollection:
				empty($resourceIds)
					? new Entity\Booking\BookingCollection()
					: $this->bookingProvider->getList(
					gridParams: new GridParams(
						filter: new BookingFilter([
							'RESOURCE_ID' => $this->getAllResourceIds($resourceCollections),
							'WITHIN' => [
								'DATE_FROM' => $dateFrom->getTimestamp(),
								'DATE_TO' => $dateTo->getTimestamp(),
							],
						]),
						select: new BookingSelect(['RESOURCES']),
					),
					userId: $userId,
				),
				searchDates: (new DatePeriod($dateFrom, $dateTo))->getDateTimeCollection(),
			);

		return new CalendarGetResourceOccupationResponse(
			freeDates: $multiResourceEachDayFirstOccurrenceResponse['foundDates'],
		);
	}

	public function getBookingsDatesAction(
		string $timezone,
		int $dateFromTs,
		int $dateToTs,
		array $filter = [],
	): CalendarGetBookingsDatesResponse
	{
		$dateFrom = (new DateTimeImmutable('@' . $dateFromTs))->setTimezone(new DateTimeZone($timezone));
		$dateTo = (new DateTimeImmutable('@' . $dateToTs))->setTimezone(new DateTimeZone($timezone));

		$bookings = $this->getBookingsByFilter(
			userId: (int)CurrentUser::get()->getId(),
			dateFrom: $dateFrom,
			dateTo: $dateTo,
			filter: $filter,
		);

		return new CalendarGetBookingsDatesResponse(
			foundDates: $this->getBookingsDates(
				bookingCollection: $bookings,
				dateFrom: $dateFrom,
				dateTo: $dateTo,
			),
			foundDatesWithCounters: $this->getBookingsDates(
				bookingCollection: $bookings,
				dateFrom: $dateFrom,
				dateTo: $dateTo,
				withCounters: true,
			),
		);
	}

	public function getBookingsDatesCountAction(
		string $timezone,
		array $filter = []
	): CalendarGetBookingsDatesCountResponse
	{
		$countRow = $this->bookingRepository
			->getQuery(new BookingFilter($filter))
			->setSelect([
				'CNT',
				'MIN_DATE_FROM',
				'MAX_DATE_FROM',
			])
			->registerRuntimeField(
				new ExpressionField('CNT', 'COUNT(*)')
			)
			->registerRuntimeField(
				new ExpressionField('MIN_DATE_FROM', 'MIN(DATE_FROM)')
			)
			->registerRuntimeField(
				new ExpressionField('MAX_DATE_FROM', 'MAX(DATE_FROM)')
			)
			->fetch()
		;

		$minDate =
			$countRow['MIN_DATE_FROM'] === null
				? null
				: (new DateTimeImmutable('@' . $countRow['MIN_DATE_FROM']))
				->setTimezone(new DateTimeZone($timezone))
				->format('Y-m-d')
		;

		$maxDate =
			$countRow['MAX_DATE_FROM'] === null
				? null
				: (new DateTimeImmutable('@' . $countRow['MAX_DATE_FROM']))
				->setTimezone(new DateTimeZone($timezone))
				->format('Y-m-d')
		;

		return new CalendarGetBookingsDatesCountResponse(
			count: (int)$countRow['CNT'],
			minDate: $minDate,
			maxDate: $maxDate,
		);
	}

	private function getBookingsByFilter(
		int $userId,
		DateTimeImmutable $dateFrom,
		DateTimeImmutable $dateTo,
		array $filter = []
	): BookingCollection
	{
		$bookings = $this->bookingProvider->getList(
			gridParams: new GridParams(
				filter: new BookingFilter(array_merge(
					$filter,
					[
						'WITHIN' => [
							'DATE_FROM' => $dateFrom->getTimestamp(),
							'DATE_TO' => $dateTo->getTimestamp(),
						],
					]
				))
			),
			userId: $userId,
		);

		$this->bookingProvider->withCounters($bookings, $userId);

		return $bookings;
	}

	private function getBookingsDates(
		BookingCollection $bookingCollection,
		DateTimeImmutable $dateFrom,
		DateTimeImmutable $dateTo,
		bool $withCounters = false,
	): DateTimeCollection
	{
		$foundDates = new DateTimeCollection();
		$searchDates = (new DatePeriod($dateFrom, $dateTo))->getDateTimeCollection();

		foreach ($searchDates as $searchDate)
		{
			foreach ($bookingCollection as $booking)
			{
				$dayDateFrom = DateTimeImmutable::createFromFormat(
					'Y-m-d H:i:s',
					$searchDate->format('Y-m-d') . ' 00:00:00'
				);
				$dayDateTo = $dayDateFrom->add(new DateInterval('P1D'));

				if ($booking->doEventsIntersect(new DatePeriod($dayDateFrom, $dayDateTo)))
				{
					if ($withCounters === false)
					{
						$foundDates->add($searchDate);

						break;
					}

					if ($booking->getCounter() > 0)
					{
						$foundDates->add($searchDate);

						break;
					}
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

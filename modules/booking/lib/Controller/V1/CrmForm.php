<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use DateTimeImmutable;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Internals\Service\FreeTime\NearestDateSlotsRequest;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Service\FreeTime\NearestDateSlotsHandler;
use DateTimeZone;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Entity\Resource\Resource;

class CrmForm extends Controller
{
	private const LOOK_AHEAD_DAYS_AUTO_SELECTION = 60;

	private Provider\BookingProvider $bookingProvider;
	private Provider\ResourceProvider $resourceProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingProvider = new Provider\BookingProvider();
		$this->resourceProvider = new Provider\ResourceProvider();
	}

	public function configureActions(): array
	{
		$publicFilter = [
			'-prefilters' => [
				ActionFilter\Authentication::class,
				ActionFilter\Csrf::class,
			],
			'+postfilters' => [
				new ActionFilter\Cors(),
			],
		];

		return [
			'getResources' => $publicFilter,
			'getOccupancy' => $publicFilter,
			'getAutoSelectionData' => $publicFilter,
		];
	}

	public function getResourcesAction(array $ids = []): array|null
	{
		try
		{
			$filter = empty($ids)
				? null
				: new Provider\Params\Resource\ResourceFilter(['ID' => $ids])
			;

			return $this->getResources($filter);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function getAutoSelectionDataAction(string $timezone, array $resourceIds = []): array|null
	{
		$currentTime = time();

		$searchPeriod = new DatePeriod(
			(new DateTimeImmutable('@' . $currentTime))
				->setTimezone(new DateTimeZone($timezone)),
			(new DateTimeImmutable('@' . $currentTime + (Time::SECONDS_IN_DAY * self::LOOK_AHEAD_DAYS_AUTO_SELECTION)))
				->setTimezone(new DateTimeZone($timezone))
		);

		$resourceFilter = [];
		if (!empty($resourceIds))
		{
			$resourceFilter['ID'] = $resourceIds;
		}
		$resourceCollection = $this->resourceProvider->getList(
			new GridParams(
				filter: new ResourceFilter($resourceFilter),
			),
			0
		);

		$bookingCollection = $this->bookingProvider->getList(
			(new GridParams(
				filter: new BookingFilter([
					'RESOURCE_ID' => $resourceCollection->getEntityIds(),
					'WITHIN' => [
						'DATE_FROM' => $searchPeriod->getDateFrom()->getTimestamp(),
						'DATE_TO' => $searchPeriod->getDateTo()->getTimestamp(),
					],
				]),
				select: new BookingSelect(['RESOURCES']),
			)),
			0
		);

		$bestResourceId = null;
		$bestDate = null;
		$bestSlots = null;

		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			$resourceId = $resource->getId();

			$datePeriodCollection = (new NearestDateSlotsHandler())(
				new NearestDateSlotsRequest(
					$resource->getSlotRanges(),
					$bookingCollection->filter(
						static function(Booking $booking) use ($resourceId)
						{
							return in_array(
								$resourceId,
								$booking->getResourceCollection()->getEntityIds(),
								true
							);
						}
					),
					$searchPeriod,
				)
			);
			if ($datePeriodCollection->isEmpty())
			{
				continue;
			}

			$date = $datePeriodCollection->getFirstCollectionItem()->getDateFrom()->format('Y-m-d');
			$slotsCnt = $datePeriodCollection->count();
			if (
				$bestResourceId === null
				|| (
					strtotime($date) < strtotime($bestDate)
					|| (
						strtotime($date) === strtotime($bestDate)
						&& $slotsCnt > $bestSlots
					)
				)
			)
			{
				$bestResourceId = $resourceId;
				$bestDate = $date;
				$bestSlots = $slotsCnt;
			}
		}

		if (!$bestResourceId)
		{
			return null;
		}

		return [
			'resourceId' => $bestResourceId,
			'date' => $bestDate,
		];
	}

	/**
	 * @throws Exception
	 */
	private function getResources(Provider\Params\Resource\ResourceFilter|null $filter = null): array
	{
		$resources = $this->resourceProvider->getList(
			gridParams: new Provider\Params\GridParams(
				filter: $filter,
			),
			userId: 0,
		);

		$response = [];
		foreach ($resources as $resource)
		{
			$response[] = [
				'id' => $resource->getId(),
				'name' => $resource->getName(),
				'typeName' => $resource->getType()->getName(),
				'slotRanges' => $resource->getSlotRanges(),
			];
		}

		return $response;
	}

	public function getOccupancyAction(array $ids, int $dateTs): array|null
	{
		try
		{
			$date = new DateTimeImmutable('@' . $dateTs);
			$datePeriod = new DatePeriod(
				dateFrom: $date,
				dateTo: $date->add(new \DateInterval('P1D')), // add 1 day
			);

			$bookings = $this->bookingProvider->getList(
				gridParams: new Provider\Params\GridParams(
					filter: new Provider\Params\Booking\BookingFilter([
						'RESOURCE_ID' => $ids,
						'WITHIN' => [
							'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
							'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
						],
					]),
					select: new Provider\Params\Booking\BookingSelect([
						'RESOURCES',
					]),
				),
				userId: 0,
			);

			$response = [];
			foreach ($bookings as $booking)
			{
				$response[] = [
					'resourcesIds' => $booking->getResourceCollection()->getEntityIds(),
					'fromTs' => $booking->getDatePeriod()->getDateFrom()->getTimestamp(),
					'toTs' => $booking->getDatePeriod()->getDateTo()->getTimestamp(),
				];
			}

			return $response;
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}
}

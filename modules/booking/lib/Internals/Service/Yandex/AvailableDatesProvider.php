<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Exception\Yandex\InternalErrorException;
use Bitrix\Booking\Internals\Exception\Yandex\ResourceNotFoundException;
use Bitrix\Booking\Internals\Exception\Yandex\ServiceNotFoundException;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\AvailableDateCollection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item\AvailableDate;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSelect;
use Bitrix\Booking\Provider\TimeProvider;
use DateTimeImmutable;
use DateTimeZone;

class AvailableDatesProvider
{
	public function __construct(
		private readonly CompanyRepository $companyRepository,
		private readonly ResourceRepositoryInterface $resourceRepository,
		private readonly BookingRepositoryInterface $bookingRepository,
		private readonly ServiceSkuProvider $serviceSkuProvider,
		private readonly TimeProvider $timeProvider
	)
	{
	}

	public function getAvailableDates(
		string $companyId,
		array $serviceIds,
		string $dateFrom,
		string $dateTo,
		string|null $resourceId = null,
	): AvailableDateCollection
	{
		$company = $this->companyRepository->getById($companyId);
		if (!$company)
		{
			throw new InternalErrorException('Company not found');
		}

		$resourceId = $resourceId !== null ? (int)$resourceId : null;
		$serviceIds = array_unique(array_map('intval', $serviceIds));

		if ($resourceId !== null)
		{
			$resource = $this->resourceRepository->getById($resourceId);
			if ($resource === null)
			{
				throw new ResourceNotFoundException();
			}
		}

		if (!empty($serviceIds))
		{
			$skus = $this->serviceSkuProvider->get($serviceIds);
			if (count($skus) !== count($serviceIds))
			{
				throw new ServiceNotFoundException();
			}
		}

		$resourceCollection = $this->getResources($serviceIds, $resourceId);
		if ($resourceCollection->isEmpty())
		{
			return new AvailableDateCollection();
		}

		$timezone = $company->getTimezone();

		$from = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$dateFrom . ' 00:00:00',
			new DateTimeZone($timezone)
		);

		$to = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$dateTo . ' 00:00:00',
			new DateTimeZone($timezone)
		);

		$searchPeriod = new DatePeriod($from, $to->modify('+1 day'));

		$bookingCollection = $this->getBookings($resourceCollection, $searchPeriod);

		return $this->findAvailableDates($resourceCollection, $bookingCollection, $searchPeriod);
	}

	private function getResources(array $serviceIds, int|null $resourceId): ResourceCollection
	{
		$resourceFilter = [
			'IS_MAIN' => true,
			'LINKED_ENTITY' => [
				'TYPE' => ResourceLinkedEntityType::Sku,
				'ID' => $serviceIds,
			],
		];

		if ($resourceId !== null)
		{
			$resourceFilter['ID'] = $resourceId;
		}

		return $this->resourceRepository->getList(
			filter: new ResourceFilter($resourceFilter),
			select: new ResourceSelect(['SETTINGS']),
		);
	}

	private function getBookings(
		ResourceCollection $resourceCollection,
		DatePeriod $searchPeriod,
	): BookingCollection
	{
		return $this->bookingRepository->getList(
			filter: new BookingFilter([
				'RESOURCE_ID' => $resourceCollection->getEntityIds(),
				'WITHIN' => [
					'DATE_FROM' => $searchPeriod->getDateFrom()->getTimestamp(),
					'DATE_TO' => $searchPeriod->getDateTo()->getTimestamp(),
				],
			]),
			select: (new BookingSelect(['RESOURCES']))->prepareSelect(),
		);
	}

	private function findAvailableDates(
		ResourceCollection $resourceCollection,
		BookingCollection $bookingCollection,
		DatePeriod $searchPeriod,
	): AvailableDateCollection
	{
		$resourceCollections = [];
		foreach ($resourceCollection as $resource)
		{
			$resourceCollections[] = new ResourceCollection($resource);
		}

		$foundDatesResponse = $this->timeProvider->getMultiResourceEachDayFirstOccurrence(
			resourceCollections: $resourceCollections,
			eventCollection: $bookingCollection,
			searchDates: $searchPeriod->getDateTimeCollection(),
		);

		$availableDateCollection = new AvailableDateCollection();
		foreach ($foundDatesResponse['foundDates'] as $date)
		{
			$availableDateCollection->add(
				new AvailableDate($date->format('Y-m-d'))
			);
		}

		return $availableDateCollection;
	}
}

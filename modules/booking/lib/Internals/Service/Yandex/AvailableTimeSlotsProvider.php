<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Exception\Yandex\InternalErrorException;
use Bitrix\Booking\Internals\Exception\Yandex\ResourceNotFoundException;
use Bitrix\Booking\Internals\Exception\Yandex\ServiceNotFoundException;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\AvailableTimeSlotCollection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item\AvailableTimeSlot;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSelect;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Provider\TimeProvider;
use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use DateTimeInterface;

class AvailableTimeSlotsProvider
{
	private const SEARCH_SLOT_STEP_SIZE_MINUTES = 30;

	public function __construct(
		private readonly CompanyRepository $companyRepository,
		private readonly ResourceRepositoryInterface $resourceRepository,
		private readonly BookingRepositoryInterface $bookingRepository,
		private readonly ServiceSkuProvider $serviceSkuProvider,
		private readonly TimeProvider $timeProvider,
	)
	{
	}

	public function getAvailableTimeSlots(
		string $companyId,
		array $serviceIds,
		string $date,
		string|null $resourceId = null,
	): AvailableTimeSlotCollection
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

		$resourceCollection = $this->resourceRepository->getList(
			filter: $this->makeResourceFilter($serviceIds, $resourceId),
			select: new ResourceSelect(),
		);
		if ($resourceCollection->isEmpty())
		{
			return new AvailableTimeSlotCollection();
		}
		$searchPeriod = $this->makeSearchPeriod($company->getTimezone(), $date);

		$foundSlots = [];
		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			$occurrencesDatePeriodCollection = $this->timeProvider->getOccurrences(
				slotRanges: $resource->getSlotRanges(),
				bookingCollection: $this->searchBookings($searchPeriod, $resource->getId()),
				searchPeriod: $searchPeriod,
				stepSize: self::SEARCH_SLOT_STEP_SIZE_MINUTES,
			);

			foreach ($occurrencesDatePeriodCollection as $occurrenceDatePeriod)
			{
				$foundSlots[$occurrenceDatePeriod->getDateFrom()->format(DateTimeInterface::ATOM)] = true;
			}
		}

		//@todo sort by time if yandex does not handle it
		$result = new AvailableTimeSlotCollection();
		foreach ($foundSlots as $foundSlot => $value)
		{
			$result->add(new AvailableTimeSlot($foundSlot));
		}

		return $result;
	}

	private function makeResourceFilter(
		array $serviceIds,
		int|null $resourceId = null
	): ResourceFilter
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
			$resourceFilter['ID'] = (int)$resourceId;
		}

		return (new ResourceFilter($resourceFilter));
	}

	private function makeSearchPeriod(string $timezone, string $date): DatePeriod
	{
		$from = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$date . ' 00:00:00',
			new DateTimeZone($timezone)
		);

		return new DatePeriod(
			dateFrom: $from,
			dateTo: $from->add(new DateInterval('P1D')),
		);
	}

	private function searchBookings(
		DatePeriod $searchDatePeriod,
		int $resourceId
	): BookingCollection
	{
		return $this->bookingRepository->getList(
			filter: new BookingFilter([
				'RESOURCE_ID' => [
					$resourceId,
				],
				'WITHIN' => [
					'DATE_FROM' => $searchDatePeriod->getDateFrom()->getTimestamp(),
					'DATE_TO' => $searchDatePeriod->getDateTo()->getTimestamp(),
				],
			]),
			select: (new BookingSelect(['RESOURCES']))->prepareSelect(),
		);
	}
}

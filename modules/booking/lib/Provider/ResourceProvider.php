<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;

class ResourceProvider
{
	private ResourceRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getResourceRepository();
	}

	public function getList(GridParams $gridParams, int $userId): ResourceCollection
	{
		return $this->repository->getList(
			limit: $gridParams->limit,
			offset: $gridParams->offset,
			filter: $gridParams->getFilter(),
			sort: $gridParams->getSort(),
			userId: $userId,
		);
	}

	public function getById(int $userId, int $resourceId): Resource|null
	{
		return $this->repository->getById(
			id: $resourceId,
			userId: $userId,
		);
	}

	public function getTotal(ResourceFilter $filter, int $userId): int
	{
		return $this->repository->getTotal(
			filter: $filter->prepareFilter(),
			userId: $userId
		);
	}

	public function withCounters(ResourceCollection $collection, int $managerId, DatePeriod|null $datePeriod = null): self
	{
		if ($collection->isEmpty())
		{
			return $this;
		}

		if ($datePeriod === null)
		{
			// default is today
			$datePeriod = new DatePeriod(
				dateFrom: new \DateTimeImmutable("today 00:00"),
				dateTo: new \DateTimeImmutable("today 24:00"),
			);
		}

		$bookingCollection = $this->getBookingCollection($collection, $managerId, $datePeriod);

		if ($bookingCollection->isEmpty())
		{
			return $this;
		}

		/** @var Resource $resource */
		foreach ($collection as $resource)
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

		return $this;
	}

	private function getBookingCollection(
		ResourceCollection $resourceCollection,
		int $managerId,
		DatePeriod $datePeriod
	): BookingCollection
	{
		$bookingProvider = new BookingProvider();
		$bookingCollection = $bookingProvider->getList(
			new GridParams(
				filter: new BookingFilter([
					'RESOURCE_ID' => $resourceCollection->getEntityIds(),
					'WITHIN' => [
						'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
						'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
					],
				]),
				select: new BookingSelect([
					'RESOURCES',
				]),
			),
			userId: $managerId,
		);

		$bookingProvider->withCounters($bookingCollection, $managerId);

		return $bookingCollection;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\ResourceDialogResponse;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Provider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity;
use Bitrix\Main\Request;
use Bitrix\Booking\Integration\Ui\EntitySelector;
use DateTimeImmutable;

class ResourceDialog extends BaseController
{
	private int $resourcesLimit = 20;

	private int $userId;
	private Provider\BookingProvider $bookingProvider;
	private Provider\ResourceProvider $resourceProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->userId = (int)CurrentUser::get()->getId();
		$this->bookingProvider = new Provider\BookingProvider();
		$this->resourceProvider = new Provider\ResourceProvider();
	}

	public function getMainResourcesAction()
	{
		return $this->handleRequest(function()
		{
			return $this->resourceProvider->getList(
				userId: $this->userId,
				filter: [
					'IS_MAIN' => true,
				],
			);
		});
	}

	public function loadByIdsAction(array $ids, int $dateTs)
	{
		return $this->handleRequest(function() use ($ids, $dateTs)
		{
			$resources = new Entity\Resource\ResourceCollection();
			if (!empty($ids))
			{
				$resources = $this->getResources([
					'MODULE_ID' => 'booking',
					'ID' => $ids,
				]);
			}

			return $this->prepareResponse($resources, $dateTs);
		});
	}

	public function fillDialogAction(int $dateTs)
	{
		return $this->handleRequest(function() use ($dateTs)
		{
			$recentResourcesIds = EntitySelector\ResourceProvider::getRecentIds();

			$resources = new Entity\Resource\ResourceCollection();
			if (!empty($recentResourcesIds))
			{
				$resources = $this->getResources([
					'MODULE_ID' => 'booking',
					'ID' => $recentResourcesIds,
				]);
			}

			return $this->prepareResponse($resources, $dateTs);
		});
	}

	public function doSearchAction(string $query, int $dateTs): ResourceDialogResponse
	{
		return $this->handleRequest(function() use ($query, $dateTs)
		{
			$resources = new Entity\Resource\ResourceCollection();
			if (!empty($query))
			{
				$resources = $this->getResources([
					'MODULE_ID' => 'booking',
					'SEARCH_QUERY' => $query,
				]);
			}

			return $this->prepareResponse($resources, $dateTs);
		});
	}

	private function getResources(array $filter): Entity\Resource\ResourceCollection
	{
		return $this->resourceProvider->getList(
			userId: $this->userId,
			limit: $this->resourcesLimit,
			filter: $filter,
		);
	}

	private function prepareResponse(
		Entity\Resource\ResourceCollection $resources,
		int $dateTs,
	): ResourceDialogResponse
	{
		$date = new DateTimeImmutable('@' . $dateTs);
		$datePeriod = new DatePeriod(
			dateFrom: $date,
			dateTo: $date->add(new \DateInterval('P1D')), // add 1 day
		);

		$bookings = $this->getBookings($this->userId, $datePeriod, $resources);

		return new ResourceDialogResponse(
			bookingCollection: $bookings,
			resourceCollection: $resources,
		);
	}

	private function getBookings(
		int $userId,
		DatePeriod $datePeriod,
		Entity\Resource\ResourceCollection $resources,
	): Entity\Booking\BookingCollection
	{
		$resourcesIds = $resources->getEntityIds();
		if (empty($resourcesIds))
		{
			return new Entity\Booking\BookingCollection();
		}

		return $this->bookingProvider->getList(
			userId: $userId,
			filter: [
				'RESOURCE_ID' => $resourcesIds,
				'WITHIN' => [
					'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
					'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
				],
			],
			select: [
				'CLIENTS',
				'RESOURCES',
				'EXTERNAL_DATA',
				'NOTE',
			],
			withCounters: true,
			withClientData: true,
			withExternalData: true,
		);
	}
}

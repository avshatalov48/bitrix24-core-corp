<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\ResourceDialogResponse;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Booking\Entity;
use Bitrix\Main\Request;
use Bitrix\Booking\Internals\Integration\Ui\EntitySelector;
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

	public function getMainResourcesAction(): ResourceCollection|null
	{
		try
		{
			return $this->resourceProvider->getList(
				gridParams: new Provider\Params\GridParams(
					filter: new Provider\Params\Resource\ResourceFilter([
						'IS_MAIN' => true,
					]),
				),
				userId: $this->userId,
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function loadByIdsAction(array $ids, int $dateTs): ResourceDialogResponse|null
	{
		try
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
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function fillDialogAction(int $dateTs): ResourceDialogResponse|null
	{
		try
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
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function doSearchAction(string $query, int $dateTs): ResourceDialogResponse|null
	{
		try
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
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	private function getResources(array $filter): Entity\Resource\ResourceCollection
	{
		return $this->resourceProvider->getList(
			gridParams: new Provider\Params\GridParams(
				limit: $this->resourcesLimit,
				filter: new Provider\Params\Resource\ResourceFilter($filter),
			),
			userId: $this->userId,
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

		$bookings = $this->bookingProvider->getList(
			new GridParams(
				filter: new BookingFilter([
					'RESOURCE_ID' => $resourcesIds,
					'WITHIN' => [
						'DATE_FROM' => $datePeriod->getDateFrom()->getTimestamp(),
						'DATE_TO' => $datePeriod->getDateTo()->getTimestamp(),
					],
				]),
				select: new BookingSelect([
					'CLIENTS',
					'RESOURCES',
					'EXTERNAL_DATA',
					'NOTE',
				]),
			),
			userId: $userId,
		);

		$this->bookingProvider
			->withCounters($bookings, $userId)
			->withClientsData($bookings)
			->withExternalData($bookings)
		;

		return $bookings;
	}
}

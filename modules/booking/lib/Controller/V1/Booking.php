<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\BookingFeature;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Service\BookingService;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;

class Booking extends BaseController
{
	public function listAction(
		PageNavigation $navigation,
		array $filter = [],
		array $sort = [],
		array $select = [],
		bool $withCounters = false,
		bool $withClientData = false,
		bool $withExternalData = false,
	): Entity\Booking\BookingCollection
	{
		return (new BookingProvider())->getList(
			userId: (int)CurrentUser::get()->getId(),
			limit: $navigation->getLimit(),
			offset: $navigation->getOffset(),
			filter: $filter,
			sort: $sort,
			select: $select,
			withCounters: $withCounters,
			withClientData: $withClientData,
			withExternalData: $withExternalData,
		);
	}

	public function getAction(int $id): Entity\Booking\Booking|null
	{
		return $this->handleRequest(function() use ($id)
		{
			return (new BookingProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				id: $id,
			);
		});
	}

	public function getIntersectionsListAction(Entity\Booking\Booking $booking): Entity\Booking\BookingCollection
	{
		return $this->handleRequest(function() use ($booking)
		{
			return (new BookingProvider())->getIntersectionsList(
				userId: (int)CurrentUser::get()->getId(),
				booking: $booking,
			);
		});
	}

	public function addAction(Entity\Booking\Booking $booking): Entity\Booking\Booking|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		return $this->handleRequest(function() use ($booking)
		{
			return (new BookingService())->create(
				userId: (int)CurrentUser::get()->getId(),
				booking: $booking,
			);
		});
	}

	public function addListAction(Entity\Booking\BookingCollection $bookingList): ?Entity\Booking\BookingCollection
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		return $this->handleRequest(function() use ($bookingList)
		{
			$result = new Entity\Booking\BookingCollection();

			foreach ($bookingList as $booking)
			{
				$result->add(
					(new BookingService())->create(
						userId: (int)CurrentUser::get()->getId(),
						booking: $booking,
					)
				);
			}

			return $result;
		});
	}

	public function updateAction(Entity\Booking\Booking $booking): Entity\Booking\Booking|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		return $this->handleRequest(function() use ($booking)
		{
			return (new BookingService())->update(
				userId: (int)CurrentUser::get()->getId(),
				booking: $booking,
			);
		});
	}

	public function deleteAction(int $id): array
	{
		return $this->handleRequest(function() use ($id)
		{
			(new BookingService())->delete(
				userId: (int)CurrentUser::get()->getId(),
				id: $id,
			);

			return [];
		});
	}

	public function deleteListAction(array $ids): array
	{
		$userId = (int)CurrentUser::get()->getId();
		foreach ($ids as $id)
		{
			$this->handleRequest(function() use ($id, $userId)
			{
				(new BookingService())->delete(
					userId: $userId,
					id: (int)$id,
				);
			});
		}

		return [];
	}
}

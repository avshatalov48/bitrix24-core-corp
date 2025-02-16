<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\BookingFeature;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Booking\Service\ResourceService;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;

class Resource extends BaseController
{
	public function listAction(
		PageNavigation $navigation,
		array $filter = [],
		array $sort = [],
	): Entity\Resource\ResourceCollection
	{
		return (new ResourceProvider())->getList(
			userId: (int)CurrentUser::get()->getId(),
			limit: $navigation->getLimit(),
			offset: $navigation->getOffset(),
			filter: $filter,
			sort: $sort,
		);
	}

	public function getAction(int $id): Entity\Resource\Resource|null
	{
		return $this->handleRequest(function() use ($id)
		{
			$response = (new ResourceProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				resourceId: $id
			);

			return $response->resource;
		});
	}

	public function addAction(
		Entity\Resource\Resource $resource,
		int|null $copies = null
	): Entity\Resource\Resource|null
	{
		if (
			!BookingFeature::isFeatureEnabled()
			&& !BookingFeature::canTurnOnTrial()
		)
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		return $this->handleRequest(function() use ($resource, $copies)
		{
			$resource = (new ResourceService())->create(
				userId: (int)CurrentUser::get()->getId(),
				resource: $resource,
				copies: $copies,
			);

			if (BookingFeature::canTurnOnTrial())
			{
				BookingFeature::turnOnTrial();
			}

			return $resource;
		});
	}

	public function updateAction(Entity\Resource\Resource $resource): Entity\Resource\Resource|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		return $this->handleRequest(function() use ($resource)
		{
			return (new ResourceService())->update(
				userId: (int)CurrentUser::get()->getId(),
				resource: $resource,
			);
		});
	}

	public function deleteAction(int $id): array
	{
		$this->deleteResource($id);

		return [];
	}

	public function deleteListAction(array $ids): array
	{
		foreach ($ids as $id)
		{
			$this->deleteResource($id);
		}

		return [];
	}

	public function hasBookingsAction(int $resourceId): bool
	{
		return !(
			Container::getBookingRepository()->getList(
				limit: 1,
				filter: new GetListFilter([
					'RESOURCE_ID' => [$resourceId],
				])
			)->isEmpty()
		);
	}

	private function deleteResource(int $id): void
	{
		$this->handleRequest(function() use ($id)
		{
			(new ResourceService())->delete(
				userId: (int)CurrentUser::get()->getId(),
				id: $id,
			);
		});
	}
}

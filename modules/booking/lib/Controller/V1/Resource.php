<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Service\BookingFeature;
use Bitrix\Booking\Command\Resource\AddResourceCommand;
use Bitrix\Booking\Command\Resource\RemoveResourceCommand;
use Bitrix\Booking\Command\Resource\UpdateResourceCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSort;
use Bitrix\Booking\Provider\ResourceProvider;
use Bitrix\Main\Engine\CurrentUser;
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
			gridParams: new GridParams(
				limit: $navigation->getLimit(),
				offset: $navigation->getOffset(),
				filter: new ResourceFilter($filter),
				sort: new ResourceSort($sort),
			),
			userId: (int)CurrentUser::get()->getId(),
		);
	}

	public function getAction(int $id): Entity\Resource\Resource|null
	{
		try
		{
			return (new ResourceProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				resourceId: $id
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
			}

	public function addAction(array $resource, int|null $copies = null): Entity\Resource\Resource|null
	{
		if (
			!BookingFeature::isFeatureEnabled()
			&& !BookingFeature::canTurnOnTrial()
		)
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return null;
		}

		if (BookingFeature::canTurnOnTrial())
		{
			BookingFeature::turnOnTrial();
		}

		try
		{
			$resource = Entity\Resource\Resource::mapFromArray($resource);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new AddResourceCommand(
			createdBy: (int)CurrentUser::get()->getId(),
			resource: $resource,
			copies: $copies,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getResource();
	}

	public function updateAction(array $resource): Entity\Resource\Resource|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return null;
		}

		if (empty($resource['id']))
		{
			$this->addError(ErrorBuilder::build('Resource identifier is not specified.'));

			return null;
		}

		$entity = Container::getResourceRepository()->getById((int)$resource['id']);
		if (!$entity)
		{
			$this->addError(ErrorBuilder::build('Resource has not been found.'));

			return null;
		}

		try
		{
			$resource = Entity\Resource\Resource::mapFromArray([...$entity->toArray(), ...$resource]);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new UpdateResourceCommand(
			updatedBy: (int)CurrentUser::get()->getId(),
			resource: $resource,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getResource();
	}

	public function deleteAction(int $id): array|null
	{
		$command = new RemoveResourceCommand(
			id: $id,
			removedBy: (int)CurrentUser::get()->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	public function deleteListAction(array $ids): array
	{
		foreach ($ids as $id)
		{
			$command = new RemoveResourceCommand(
				id: $id,
				removedBy: (int)CurrentUser::get()->getId(),
			);

			$command->run();
		}

		return [];
	}

	public function hasBookingsAction(int $resourceId): bool
	{
		return !(
			Container::getBookingRepository()->getList(
				limit: 1,
				filter: new BookingFilter(['RESOURCE_ID' => [$resourceId]]),
			)->isEmpty()
		);
	}
}

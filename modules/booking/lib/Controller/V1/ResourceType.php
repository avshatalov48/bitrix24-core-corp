<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Command\ResourceType\AddResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\RemoveResourceTypeCommand;
use Bitrix\Booking\Command\ResourceType\UpdateResourceTypeCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\ResourceType\ResourceTypeFilter;
use Bitrix\Booking\Provider\Params\ResourceType\ResourceTypeSort;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UI\PageNavigation;

class ResourceType extends BaseController
{
	public function listAction(
		PageNavigation $navigation,
		array $filter = [],
		array $sort = [],
	): Entity\ResourceType\ResourceTypeCollection
	{
		return (new ResourceTypeProvider())->getList(
			new GridParams(
				limit: $navigation->getLimit(),
				offset: $navigation->getOffset(),
				filter: new ResourceTypeFilter($filter),
				sort: new ResourceTypeSort($sort),
			),
			userId: (int)CurrentUser::get()->getId(),
		);
	}

	public function getAction(int $id): Entity\ResourceType\ResourceType|null
	{
		try
		{
			return (new ResourceTypeProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				id: $id
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function addAction(array $resourceType): Entity\ResourceType\ResourceType|null
	{
		try
		{
			$resourceType = Entity\ResourceType\ResourceType::mapFromArray($resourceType);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new AddResourceTypeCommand(
			createdBy: (int) CurrentUser::get()->getId(),
			resourceType: $resourceType,
			rangeCollection: null,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getResourceType();
	}

	public function updateAction(array $resourceType): Entity\ResourceType\ResourceType|null
	{
		if (empty($resourceType['id']))
		{
			$this->addError(ErrorBuilder::build('Resource type identifier is not specified.'));

			return null;
		}

		$entity = Container::getResourceTypeRepository()->getById((int)$resourceType['id']);
		if (!$entity)
		{
			$this->addError(ErrorBuilder::build('Resource type has not been found.'));

			return null;
		}

		try
		{
			$resourceType = Entity\ResourceType\ResourceType::mapFromArray([...$entity->toArray(), ...$resourceType]);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new UpdateResourceTypeCommand(
			updatedBy: (int) CurrentUser::get()->getId(),
			resourceType: $resourceType,
			rangeCollection: null,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getResourceType();
	}

	public function deleteAction(int $id): array|null
	{
		$command = new RemoveResourceTypeCommand(
			id: $id,
			removedBy: (int) CurrentUser::get()->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return [];
	}
}

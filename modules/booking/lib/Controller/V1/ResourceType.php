<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Booking\Service\ResourceTypeService;
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
			userId: (int)CurrentUser::get()->getId(),
			limit: $navigation->getLimit(),
			offset: $navigation->getOffset(),
			filter: $filter,
			sort: $sort,
		);
	}

	public function getAction(int $id): Entity\ResourceType\ResourceType|null
	{
		return $this->handleRequest(function() use ($id)
		{
			return (new ResourceTypeProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				id: $id
			);
		});
	}

	public function addAction(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType|null
	{
		return $this->handleRequest(function() use ($resourceType)
		{
			return (new ResourceTypeService())->create(
				userId: (int)CurrentUser::get()->getId(),
				resourceType: $resourceType,
			);
		});
	}

	public function updateAction(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType|null
	{
		return $this->handleRequest(function() use ($resourceType)
		{
			return (new ResourceTypeService())->update(
				userId: (int)CurrentUser::get()->getId(),
				resourceType: $resourceType,
			);
		});
	}

	public function deleteAction(int $id): void
	{
		$this->handleRequest(function() use ($id)
		{
			(new ResourceTypeService())->delete(
				userId: (int)CurrentUser::get()->getId(),
				id: $id,
			);
		});
	}
}

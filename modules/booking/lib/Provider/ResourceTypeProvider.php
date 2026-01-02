<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Provider\Params\GridParams;

class ResourceTypeProvider
{
	private ResourceTypeRepositoryInterface $repository;
	private ResourceRepositoryInterface $resourceRepository;

	public function __construct()
	{
		$this->repository = Container::getResourceTypeRepository();
		$this->resourceRepository = Container::getResourceRepository();
	}

	public function getList(GridParams $gridParams, int $userId): ResourceTypeCollection
	{
		return $this->repository->getList(
			limit: $gridParams->limit,
			offset: $gridParams->offset,
			filter: $gridParams->filter,
			sort: $gridParams->getSort(),
			userId: $userId,
		);
	}

	public function getById(int $userId, int $id): Entity\ResourceType\ResourceType|null
	{
		return $this->repository->getById(id: $id, userId: $userId);
	}

	public function withResourcesCnt(ResourceTypeCollection $resourceTypeCollection): self
	{
		$resourceTypeCount = $this->resourceRepository->getResourceTypeCount($resourceTypeCollection->getEntityIds());

		foreach ($resourceTypeCollection as $resourceType)
		{
			$resourceType->setResourcesCnt($resourceTypeCount[$resourceType->getId()] ?? null);
		}

		return $this;
	}

	public function withResourceCnt(Entity\ResourceType\ResourceType $resourceType): self
	{
		$this->withResourcesCnt(new ResourceTypeCollection($resourceType));

		return $this;
	}
}

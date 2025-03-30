<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;
use Bitrix\Booking\Provider\Params\GridParams;

class ResourceTypeProvider
{
	private ResourceTypeRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getResourceTypeRepository();
	}

	public function getList(GridParams $gridParams, int $userId): ResourceTypeCollection
	{
		return $this->repository->getList(
			limit: $gridParams->limit,
			offset: $gridParams->offset,
			filter: $gridParams->getFilter(),
			sort: $gridParams->getSort(),
			userId: $userId,
		);
	}

	public function getById(int $userId, int $id): Entity\ResourceType\ResourceType|null
	{
		return $this->repository->getById(id: $id, userId: $userId);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\SortInterface;

interface ResourceTypeRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
	): Entity\ResourceType\ResourceTypeCollection;
	public function getById(int $id): Entity\ResourceType\ResourceType|null;
	public function getByModuleIdAndCode(string $moduleId, string $code): Entity\ResourceType\ResourceType|null;
	public function save(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType;
	public function remove(int $resourceTypeId): void;
}

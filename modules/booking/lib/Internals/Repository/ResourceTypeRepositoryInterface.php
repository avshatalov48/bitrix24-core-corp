<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\Params\FilterInterface;

interface ResourceTypeRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		array|null $sort = null,
		int|null $userId = null,
	): Entity\ResourceType\ResourceTypeCollection;
	public function getById(int $id, int|null $userId = null): Entity\ResourceType\ResourceType|null;
	public function isExists(int $id): bool;
	public function getByModuleIdAndCode(string $moduleId, string $code): Entity\ResourceType\ResourceType|null;
	public function save(Entity\ResourceType\ResourceType $resourceType): int;
	public function remove(int $resourceTypeId): void;
}

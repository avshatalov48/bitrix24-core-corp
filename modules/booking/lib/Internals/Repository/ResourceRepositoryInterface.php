<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface ResourceRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		ConditionTree|null $filter = null,
		array|null $sort = null,
		int|null $userId = null,
	): Entity\Resource\ResourceCollection;
	public function getTotal(ConditionTree|null $filter = null, int|null $userId = null): int;
	public function getById(int $id, int|null $userId = null): Entity\Resource\Resource|null;
	public function save(Entity\Resource\Resource $resource): int;
	public function remove(int $resourceId): void;
}

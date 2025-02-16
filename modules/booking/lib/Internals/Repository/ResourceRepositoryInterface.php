<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\SortInterface;

interface ResourceRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
	): Entity\Resource\ResourceCollection;
	public function getTotal(FilterInterface|null $filter = null): int;
	public function getById(int $id): Entity\Resource\Resource|null;
	public function save(Entity\Resource\Resource $resource): Entity\Resource\Resource;
	public function remove(int $resourceId): void;
}

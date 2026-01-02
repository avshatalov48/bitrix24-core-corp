<?php

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface WaitListItemRepositoryInterface
{
	public function getList(
		int $limit = null,
		int $offset = null,
		ConditionTree|null $filter = null,
		array|null $sort = null,
		array|null $select = null,
		int|null $userId = null,
	): Entity\WaitListItem\WaitListItemCollection;

	public function save(Entity\WaitListItem\WaitListItem $waitListItem): int;

	public function getById(int $waitListItemId, int $userId = 0): Entity\WaitListItem\WaitListItem|null;

	public function remove(int $id): void;
}

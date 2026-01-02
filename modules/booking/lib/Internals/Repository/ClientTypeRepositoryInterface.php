<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface ClientTypeRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		ConditionTree|null $filter = null,
		array|null $sort = null,
	): Entity\Client\ClientTypeCollection;

	public function getById(int $id, int $userId = 0): Entity\Client\ClientType|null;
}
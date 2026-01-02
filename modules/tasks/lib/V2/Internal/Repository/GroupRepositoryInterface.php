<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface GroupRepositoryInterface
{
	public function getById(int $id): ?Entity\Group;

	public function getMembers(int $id): Entity\UserCollection;

	public function getType(int $id): ?string;

	public function getByIds(array $ids): Entity\GroupCollection;
}

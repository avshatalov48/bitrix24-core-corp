<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface StageRepositoryInterface
{
	public function getByGroupId(int $groupId): ?Entity\StageCollection;

	public function getById(int $id): ?Entity\Stage;
}
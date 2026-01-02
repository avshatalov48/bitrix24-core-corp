<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface SubTaskRepositoryInterface
{
	public function containsSubTasks(int $parentId): bool;
}

<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface ParentTaskRepositoryInterface
{
	public function getParentId(int $taskId): ?int;
}

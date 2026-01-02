<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Task\Select;

interface TaskReadRepositoryInterface
{
	public function getById(int $id, ?Select $select = null): ?Entity\Task;

	public function getAttachmentIds(int $taskId): array;
}

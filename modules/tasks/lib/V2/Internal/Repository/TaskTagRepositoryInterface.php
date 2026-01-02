<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\TagCollection;

interface TaskTagRepositoryInterface
{
	public function getById(int $taskId): TagCollection;

	public function getByIds(array $taskIds): TagCollection;

	public function invalidate(int $taskId): void;
}

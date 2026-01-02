<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface TaskRepositoryInterface
{
	public function getById(int $id): ?Entity\Task;

	public function save(Entity\Task $entity): int;

	public function delete(int $id, bool $safe = true): void;

	public function isExists(int $id): bool;

	public function invalidate(int $taskId);
}

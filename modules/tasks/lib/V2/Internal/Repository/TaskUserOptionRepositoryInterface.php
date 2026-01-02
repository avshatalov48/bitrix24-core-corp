<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;
use Bitrix\Tasks\V2\Internal\Entity;

interface TaskUserOptionRepositoryInterface
{
	public function get(int $taskId, ?int $userId = null): Entity\Task\UserOptionCollection;

	public function isSet(int $code, int $taskId, int $userId): bool;

	public function add(Entity\Task\UserOption $userOption): void;

	public function delete(array $codes = [], int $taskId = 0, int $userId = 0): void;
}

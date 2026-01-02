<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface ElapsedTimeRepositoryInterface
{
	public function save(Entity\Task\ElapsedTime $elapsedTime): int;

	public function delete(int $id): void;

	public function getSum(int $taskId): int;
}
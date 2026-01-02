<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface TaskScenarioRepositoryInterface
{
	public function getById(int $taskId): array;

	public function save(int $taskId, array $scenarios): void;

	public function delete(int $taskId): void;
}
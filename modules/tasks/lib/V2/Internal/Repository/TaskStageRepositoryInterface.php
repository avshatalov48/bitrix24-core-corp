<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface TaskStageRepositoryInterface
{
	public function add(int $taskId, int $stageId): int;

	public function update(int $id, int $stageId): void;

	public function upsert(int $taskId, int $stageId): int;

	public function deleteById(int ...$ids): void;

	public function deleteByTaskId(int $taskId): void;

	public function deleteByStageId(int $stageId): void;
}
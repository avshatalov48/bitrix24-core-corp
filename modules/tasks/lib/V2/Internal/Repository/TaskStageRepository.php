<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Kanban\TaskStageTable;

class TaskStageRepository implements TaskStageRepositoryInterface
{
	public function getById(int $id): ?array
	{
		$row = TaskStageTable::query()
			->setSelect(['TASK_ID', 'STAGE_ID'])
			->where('ID', $id)
			->fetch();

		if (!is_array($row))
		{
			return null;
		}

		return $row;
	}

	public function add(int $taskId, int $stageId): int
	{
		$data = [
			'TASK_ID' => $taskId,
			'STAGE_ID' => $stageId,
		];

		$result = TaskStageTable::add($data);
		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}

		return (int)$result->getId();
	}

	public function update(int $id, int $stageId): void
	{
		TaskStageTable::update($id, ['STAGE_ID' => $stageId]);
	}

	public function upsert(int $taskId, int $stageId): int
	{
		$result = TaskStageTable::addMerge(['TASK_ID' => $taskId, 'STAGE_ID' => $stageId]);

		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}

		return (int)$result->getId();
	}

	public function deleteById(int ...$ids): void
	{
		TaskStageTable::deleteByFilter(['@ID' => $ids]);
	}

	public function deleteByTaskId(int $taskId): void
	{
		TaskStageTable::deleteByFilter(['TASK_ID' => $taskId]);
	}

	public function deleteByStageId(int $stageId): void
	{
		TaskStageTable::deleteByFilter(['STAGE_ID' => $stageId]);
	}
}
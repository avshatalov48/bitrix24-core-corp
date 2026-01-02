<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface;

class TaskStageService
{
	public function __construct(
		private readonly TaskStageRepositoryInterface $taskStageRepository,
	)
	{

	}

	public function move(int $id, int $stageId): void
	{
		try
		{
			$this->taskStageRepository->update($id, $stageId);
		}
		catch (DuplicateEntryException)
		{
			$this->taskStageRepository->deleteById($id);
		}
	}

	public function upsert(int $taskId, int $stageId): int
	{
		return $this->taskStageRepository->upsert($taskId, $stageId);
	}

	public function clearRelations(array $relationIds): void
	{
		if (empty($relationIds))
		{
			return;
		}

		$this->taskStageRepository->deleteById(...$relationIds);
	}

	public function clearStage(int $stageId): void
	{
		$this->taskStageRepository->deleteByStageId($stageId);
	}
}

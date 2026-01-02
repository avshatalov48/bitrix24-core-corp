<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\ScenarioTable;

class TaskScenarioRepository implements TaskScenarioRepositoryInterface
{
	public function getById(int $taskId): array
	{
		$rows = ScenarioTable::query()
			->setSelect(['SCENARIO'])
			->where('TASK_ID', $taskId)
			->fetchAll();

		return array_column($rows, 'SCENARIO');
	}

	public function save(int $taskId, array $scenarios): void
	{
		$rows = [];
		foreach ($scenarios as $scenario)
		{
			$rows[] = [
				'TASK_ID' => $taskId,
				'SCENARIO' => $scenario,
			];
		}

		ScenarioTable::addInsertIgnoreMulti($rows, true);
	}

	public function delete(int $taskId): void
	{
		ScenarioTable::delete($taskId);
	}
}
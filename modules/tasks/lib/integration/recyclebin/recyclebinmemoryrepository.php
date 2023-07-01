<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

class RecycleBinMemoryRepository implements RecyclebinTasksRepositoryInterface
{
	private array $tasksCollection;

	public function __construct(array $tasksCollection = [])
	{
		$this->tasksCollection = $tasksCollection;
	}

	public function totalTasksToRemove(TasksMaxDaysInRecycleBin $maxDaysTTL): int
	{
		$total = 0;
		foreach ($this->tasksCollection as $task)
		{
			if (isset($task['TIMESTAMP']) && $maxDaysTTL->isOlderThanMaxTTL($task['TIMESTAMP']))
			{
				$total++;
			}
		}
		return $total;
	}

	public function removeTasksFromRecycleBin(
		TasksMaxDaysInRecycleBin $maxDaysTTL,
		TasksMaxToRemoveFromRecycleBin $maxTasksToRemove
	): void
	{
		$removed = 0;
		foreach ($this->tasksCollection as $key => $task)
		{
			if ($removed >= $maxTasksToRemove->getValue())
			{
				return;
			}

			if (isset($task['TIMESTAMP']) && $maxDaysTTL->isOlderThanMaxTTL($task['TIMESTAMP'])) {
				unset($this->tasksCollection[$key]);
				$removed++;
			}
		}
	}
}
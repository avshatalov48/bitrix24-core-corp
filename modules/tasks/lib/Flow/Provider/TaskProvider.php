<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\MemberTable;

final class TaskProvider
{
	/**
	 * The method returns a map of task id => director id.
	 *
	 * @param array $taskIds Task ids.
	 * @return array
	 */
	public function getTasksDirector(array $taskIds): array
	{
		$tasksDirector = [];

		if (empty($taskIds))
		{
			return $tasksDirector;
		}

		$directors = MemberTable::query()
			->setSelect(['TASK_ID', 'USER_ID'])
			->where('TYPE', RoleDictionary::ROLE_DIRECTOR)
			->whereIn('TASK_ID', $taskIds)
			->exec()
			->fetchCollection();

		foreach ($directors as $director)
		{
			$tasksDirector[$director->getTaskId()] = $director->getUserId();
		}

		return $tasksDirector;
	}

	public function getTotalTasks(array $flowIds): array
	{
		$result = array_fill_keys($flowIds, 0);

		$rows = (
			FlowTaskTable::query()
				->setSelect(['FLOW_ID', Query::expr('CNT')->count('ID')])
				->whereIn('FLOW_ID', $flowIds)
				->exec()
				->fetchAll()
		);
		foreach ($rows as $row)
		{
			$result[$row['FLOW_ID']] = (int)$row['CNT'];
		}

		return $result;
	}

	/**
	 * The method returns task identifiers with a specific task status in flows.
	 *
	 * @param int $flowId
	 * @param string $flowTaskStatus Status::FLOW_PENDING | Status::FLOW_AT_WORK | Status::FLOW_COMPLETED
	 * @return int
	 */
	public function getTotalTasksWithStatus(int $flowId, string $flowTaskStatus): int
	{
		$statuses = Status::STATUS_MAP[$flowTaskStatus];
		if (empty($statuses))
		{
			return 0;
		}

		$subQuery = FlowTaskTable::query()
			->setSelect(['TASK_ID'])
			->where('FLOW_ID', $flowId)
			->getQuery();

		$query = TaskTable::query()
			->setSelect([Query::expr('CNT')->count('ID')])
			->whereIn('ID', new SqlExpression($subQuery))
			->whereIn('STATUS', $statuses);

		return (int)($query->exec()->fetch()['CNT'] ?? 0);
	}

	public function getMapIds(array $taskIds): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		$map = [];

		$queryResult = FlowTaskTable::getList([
			'select' => ['TASK_ID', 'FLOW_ID'],
			'filter' => ['TASK_ID' => $taskIds],
		]);
		foreach ($queryResult->fetchCollection() as $flowTask)
		{
			$map[$flowTask->getTaskId()] = $flowTask->getFlowId();
		}

		return $map;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\ParameterTable;

class TaskParameterRepository implements TaskParameterRepositoryInterface
{
	public function invalidate(int $taskId): void
	{
	}

	public function matchesSubTasksTime(int $taskId): bool
	{
		return $this->getParameter($taskId, ParameterTable::PARAM_SUBTASKS_TIME);
	}

	public function isResultRequired(int $taskId): bool
	{
		return $this->getParameter($taskId, ParameterTable::PARAM_RESULT_REQUIRED);
	}

	public function allowsChangeDatePlan(int $taskId): bool
	{
		return $this->getParameter($taskId, ParameterTable::PARAM_ALLOW_CHANGE_DATE_PLAN);
	}

	private function getParameter(int $taskId, int $code): bool
	{
		$res = ParameterTable::query()
			->setSelect(['ID'])
			->where('TASK_ID', $taskId)
			->whereIn('CODE', $code)
			->where('VALUE', 'Y')
			->setLimit(1)
			->fetch()
		;

		return $res && (int)$res['ID'] > 0;
	}
}

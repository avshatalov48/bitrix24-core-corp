<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\ParameterTable;

class InMemoryTaskParameterRepository implements TaskParameterRepositoryInterface
{
	private array $cache = [];

	public function invalidate(int $taskId): void
	{
		unset($this->cache[$taskId]);
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
		return isset($this->getParameters($taskId)[$code]);
	}

	private function getParameters(int $taskId): array
	{
		$this->cache[$taskId] ??= array_column($this->fetchParameters($taskId), 'CODE', 'CODE');

		return $this->cache[$taskId];
	}

	private function fetchParameters(int $taskId): array
	{
		$codes = ParameterTable::paramsList();

		return ParameterTable::query()
			->setSelect(['CODE'])
			->where('TASK_ID', $taskId)
			->whereIn('CODE', $codes)
			->where('VALUE', 'Y')
			->setLimit(count($codes))
			->fetchAll()
		;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface TaskParameterRepositoryInterface
{
	public function invalidate(int $taskId): void;

	public function matchesSubTasksTime(int $taskId): bool;

	public function isResultRequired(int $taskId): bool;

	public function allowsChangeDatePlan(int $taskId): bool;
}

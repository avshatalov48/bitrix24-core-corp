<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Task;

use Bitrix\Main\Type\DateTime;

interface TaskRepositoryInterface
{
	public function getCreatedAfterTasksCount(int $userId, DateTime $date): int;
	public function getOnePersonTasksCount(int $userId): int;
}
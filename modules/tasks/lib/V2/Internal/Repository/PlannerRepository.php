<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\Collection;
use CUserOptions;

class PlannerRepository implements PlannerRepositoryInterface
{
	public function add(int $userId, array $taskIds): void
	{
		Collection::normalizeArrayValuesByInt($taskIds, false);

		$currentTaskIds = (array)$this->getAll($userId);
		$taskIds = array_merge($currentTaskIds, $taskIds);

		Collection::normalizeArrayValuesByInt($taskIds, false);

		if ($currentTaskIds !== $taskIds)
		{
			$this->save($userId, $taskIds);
		}
	}

	public function delete(int $userId, array $taskIds): void
	{
		Collection::normalizeArrayValuesByInt($taskIds, false);

		$currentTaskIds = (array)$this->getAll($userId);
		$taskIds = array_diff($currentTaskIds, $taskIds);

		Collection::normalizeArrayValuesByInt($taskIds, false);

		if ($currentTaskIds !== $taskIds)
		{
			$this->save($userId, $taskIds);
		}
	}

	public function save(int $userId, array $taskIds): void
	{
		Collection::normalizeArrayValuesByInt($taskIds, false);

		CUserOptions::SetOption(
			category: 'tasks',
			name:     'current_tasks_list',
			value:    $taskIds,
			user_id:  $userId
		);

		global $CACHE_MANAGER;

		$CACHE_MANAGER->ClearByTag('tasks_user_' . $userId);
	}

	public function getAll(int $userId): ?array
	{
		$taskIds = CUserOptions::GetOption(
			category: 'tasks',
			name: 'current_tasks_list',
			default_value: null,
			user_id: $userId,
		);

		if (!is_array($taskIds))
		{
			return null;
		}

		Collection::normalizeArrayValuesByInt($taskIds, false);

		return $taskIds;
	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\V2\Internal\Integration\TimeMan\Service\UserService;
use Bitrix\Tasks\V2\Internal\Repository\PlannerRepositoryInterface;

class PlannerService
{
	private array $syncCache = [];

	public function __construct(
		private readonly PlannerRepositoryInterface $plannerRepository,
		private readonly UserService $userService,
		private readonly TaskList $taskProvider,
	)
	{

	}

	public function merge(int $userId, array $toAdd, array $toDelete): int
	{
		Collection::normalizeArrayValuesByInt($toAdd, false);
		Collection::normalizeArrayValuesByInt($toDelete, false);

		$currentTaskIds = $this->plannerRepository->getAll($userId) ?? [];

		$taskIds = array_merge($currentTaskIds, $toAdd);
		$taskIds = array_unique($taskIds);

		$taskIds = array_diff($taskIds, $toDelete);

		if (!$this->isEquals($currentTaskIds, $taskIds))
		{
			$this->plannerRepository->save($userId, $taskIds);
		}

		return empty($taskIds) ? 0 : (int)end($taskIds);
	}

	public function syncAndGetActualUserTaskIds(int $userId): array
	{
		$taskIds = $this->plannerRepository->getAll($userId);

		if ($taskIds === null)
		{
			$taskIds = $this->userService->getTasks($userId);

			if ($taskIds !== null)
			{
				$this->plannerRepository->save($userId, $taskIds);
			}
		}

		if (!is_array($taskIds))
		{
			$taskIds = [];
		}

		if (!empty($taskIds) && !isset($this->syncCache[$userId]))
		{
			$query = (new TaskQuery($userId))
				->skipAccessCheck()
				->setSelect(['ID'])
				->addWhere('@ID', array_slice($taskIds, 0, 30));

			$existingTasks = $this->taskProvider->getList($query);

			$existingTaskIds = array_column($existingTasks, 'ID');

			Collection::normalizeArrayValuesByInt($existingTaskIds, false);

			$toSync = array_intersect($taskIds, $existingTaskIds);


			if (count($taskIds) !== count($existingTaskIds))
			{
				$this->plannerRepository->save($userId, $toSync);

				$taskIds = $toSync;
			}

			$this->syncCache[$userId] = true;
		}

		return $taskIds;
	}

	private function isEquals(array $a, array $b): bool
	{
		Collection::normalizeArrayValuesByInt($a);
		Collection::normalizeArrayValuesByInt($b);

		return $a === $b;
	}
}

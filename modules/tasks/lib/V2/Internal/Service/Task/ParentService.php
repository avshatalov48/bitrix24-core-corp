<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\ParentTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use CTasks;

class ParentService
{
	public function __construct(
		private readonly UpdateService $updateService,
		private readonly ParentTaskRepositoryInterface $parentTaskRepository,
	)
	{
	}

	public function setParent(int $taskId, int $parentId, int $userId): Entity\Task
	{
		$entity = new Entity\Task(
			id: $taskId, parent: new Entity\Task(id: $parentId),
		);

		$config = new UpdateConfig(userId: $userId);

		[$task] = $this->updateService->update(task: $entity, config: $config);

		return $task;
	}

	public function deleteParent(int $taskId, int $userId): Entity\Task
	{
		$entity = new Entity\Task(
			id: $taskId, parent: new Entity\Task(id: 0),
		);
		$config = new UpdateConfig(userId: $userId);

		[$task] = $this->updateService->update(task: $entity, config: $config);

		return $task;
	}

	public function isDescendantOf(int $descendantId, int $ancestorId): bool
	{
		$met = [];

		$met[$descendantId] = true;
		$i = 0;

		while ($i < 1000)
		{
			$parentId = (int)$this->getParentId($descendantId);

			if (isset($met[$parentId])) // chain is loopy
			{
				return false;
			}

			if ($parentId <= 0) // no parent anymore
			{
				return false;
			}

			if ($parentId === $ancestorId) // found
			{
				return true;
			}

			$met[$parentId] = true;
			$descendantId = $parentId;

			$i++;
		}

		return false;
	}

	public function getParentId(int $taskId): ?int
	{
		if ($taskId <= 0)
		{
			return null;
		}

		return $this->parentTaskRepository->getParentId($taskId);
	}
}


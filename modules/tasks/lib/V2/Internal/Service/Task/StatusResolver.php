<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\V2\Internal\Entity\Task\Status;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;

class StatusResolver
{
	public function __construct(
		private readonly TaskRepositoryInterface $taskRepository,
	)
	{

	}

	public function resolveForComplete(int $taskId, int $userId): Status
	{
		$task = $this->taskRepository->getById($taskId);
		if ($task === null)
		{
			throw new TaskNotExistsException();
		}

		$creatorId = $task->creator?->getId();
		$responsibleId = $task->responsible?->getId();

		if (
			!$task->needsControl
			|| $creatorId === $responsibleId
			|| $creatorId === $userId
		)
		{
			return Status::Completed;
		}

		if ($task->status === Status::SupposedlyCompleted)
		{
			if (User::isSuper($userId))
			{
				return Status::Completed;
			}

			if (User::isBoss($creatorId, $userId))
			{
				return Status::Completed;
			}
		}

		return Status::SupposedlyCompleted;
	}
}
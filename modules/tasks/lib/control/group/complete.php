<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Status;
use \Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\TaskObject;

class Complete
{
	public function runBatch(int $userId, array $taskIds): array
	{
		$result = [];
		$registry = TaskRegistry::getInstance();
		$registry->load($taskIds, true);
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$task = $registry->getObject($id, true);
			if (!$task)
			{
				continue;
			}

			$status = $this->getStatus($task, $userId);

			$result[] = [
				$control->update($id, [
					'STATUS' => $status
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}

	private function getStatus(TaskObject $task, int $userId): int
	{
		if (!$task->getTaskControl())
		{
			return Status::COMPLETED;
		}

		if ($task->getCreatedBy() === $userId)
		{
			return Status::COMPLETED;
		}

		if ($task->getCreatedBy() === $task->getResponsibleId())
		{
			return Status::COMPLETED;
		}

		if ((int)$task->getStatus() === Status::SUPPOSEDLY_COMPLETED)
		{
			if (User::isSuper($userId) || User::isBoss($task->getCreatedBy(), $userId))
			{
				return Status::COMPLETED;
			}
		}

		return Status::SUPPOSEDLY_COMPLETED;
	}
}

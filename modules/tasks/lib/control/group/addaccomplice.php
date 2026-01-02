<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;

class AddAccomplice
{
	public function runBatch(int $userId, array $taskIds, int $accompliceId): array
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

			$members = $this->prepareMembers($task, $accompliceId);

			$result[] = [
				$control->update($id, $members),
				'taskId' => $id,
			];
		}

		return $result;
	}

	private function prepareMembers(TaskObject $task, int $accompliceId):array
	{
		$accomplices['ACCOMPLICES'] = $task->getMemberList()->getAccompliceIds();
		$accomplices['ACCOMPLICES'][] = $accompliceId;

		return $accomplices;
	}
}

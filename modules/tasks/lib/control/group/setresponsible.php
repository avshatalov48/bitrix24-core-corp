<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;

class SetResponsible
{
	public function runBatch(int $userId, array $taskIds, int $responsibleId): array
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

			$members = $this->prepareMembers($task, $responsibleId);

			$result[] = [
				$control->update($id, $members),
				'taskId' => $id,
			];
		}

		return $result;
	}

	private function prepareMembers(TaskObject $task, int $responsibleId): array
	{
		$members['RESPONSIBLE_ID'] = $responsibleId;

		if($task->isScrum())
		{
			return $members;
		}

		$allMembers = $task->getMemberList();

		$members['AUDITORS'] = $allMembers->getAuditorIds();
		$oldResponsibleId = $allMembers->getResponsible();

		if ($oldResponsibleId !== null && $responsibleId !== $oldResponsibleId)
		{
			$members['AUDITORS'][] = $oldResponsibleId;
		}

		return $members;
	}
}

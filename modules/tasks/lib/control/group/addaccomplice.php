<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Member\Config\WorkConfig;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class AddAccomplice
{
	private const ROLE = RoleDictionary::ROLE_ACCOMPLICE;

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

			$allMembers = $task->getMemberList();
			$oldAccomplice = [];

			foreach ($allMembers as $member)
			{
				if ($member->getType() === self::ROLE)
				{
					$oldAccomplice[] = $member->getUserId();
				}
			}

			$newAccomplice = array_merge([$accompliceId], $oldAccomplice);
			$arAccomplice['ACCOMPLICES'] = $newAccomplice;

			$result[] = [
				$control->update($id, $arAccomplice),
				'taskId' => $id,
			];
		}

		return $result;
	}
}

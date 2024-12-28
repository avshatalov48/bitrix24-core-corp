<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Member\Config\WorkConfig;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class AddAuditor
{
	private const ROLE = RoleDictionary::ROLE_AUDITOR;

	public function runBatch(int $userId, array $taskIds, int $auditorId): array
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
			$oldAuditor = [];

			foreach ($allMembers as $member)
			{
				if ($member->getType() === self::ROLE)
				{
					$oldAuditor[] = $member->getUserId();
				}
			}

			$newAuditor = array_merge([$auditorId], $oldAuditor);
			$arAuditor['AUDITORS'] = $newAuditor;

			$result[] = [
				$control->update($id, $arAuditor),
				'taskId' => $id,
			];
		}

		return $result;
	}
}

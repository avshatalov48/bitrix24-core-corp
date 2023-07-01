<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Slider\Path\PathMaker;

class TaskPathMaker
{
	public static function getPathMaker(int $taskId, int $userId, string $action = PathMaker::DEFAULT_ACTION): ?PathMaker
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$task = TaskObject::getObject($taskId);
		$groupId = $task->getGroupId();

		if ($groupId !== 0)
		{
			return new \Bitrix\Tasks\Slider\Path\TaskPathMaker($taskId, $action, $groupId, PathMaker::GROUP_CONTEXT);
		}

		return new \Bitrix\Tasks\Slider\Path\TaskPathMaker($taskId, $action, $userId, PathMaker::PERSONAL_CONTEXT);
	}
}
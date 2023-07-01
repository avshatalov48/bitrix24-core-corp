<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\ActionDictionary;

class TaskAccessController
{
	public static function canEdit(int $taskId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_EDIT, $taskId);
	}

	public static function canRemove(int $taskId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_REMOVE, $taskId);
	}

	public static function canComplete(int $taskId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_COMPLETE, $taskId);
	}

	public static function canCompleteResult(int $taskId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_COMPLETE_RESULT, $taskId);
	}
}
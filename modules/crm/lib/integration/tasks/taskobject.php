<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class TaskObject
{
	public static function getObject(int $taskId, bool $withRelations = false): ?\Bitrix\Tasks\Internals\TaskObject
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		return TaskRegistry::getInstance()->getObject($taskId, $withRelations);
	}

	public static function isMember(int $taskId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		return TaskModel::createFromId($taskId)->isMember($userId);
	}


}
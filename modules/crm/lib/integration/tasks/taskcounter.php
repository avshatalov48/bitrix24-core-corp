<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Counter;

class TaskCounter
{
	public static function getCommentsCount(int $taskId, $userId): int
	{
		if (!Loader::includeModule('tasks'))
		{
			return 0;
		}

		return Counter::getInstance($userId)->getCommentsCount([$taskId])[$taskId];
	}
}
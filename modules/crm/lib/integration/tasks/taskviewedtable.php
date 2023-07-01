<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\ViewedTable;

class TaskViewedTable
{
	public static function set(int $taskId, int $userId): void
	{
		if (!Loader::includeModule('tasks'))
		{
			return;
		}

		ViewedTable::set($taskId, $userId);
	}
}
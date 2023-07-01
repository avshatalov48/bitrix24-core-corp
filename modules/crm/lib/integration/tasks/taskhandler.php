<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Control\Task;

class TaskHandler
{
	public static function getHandler(int $userId = 0): ?Task
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		return new Task($userId);
	}
}
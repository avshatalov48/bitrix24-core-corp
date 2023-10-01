<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;

class Task2ActivityPriority
{
	public static function getPriority(int $taskPriority): int
	{
		if (!Loader::includeModule('tasks'))
		{
			return \CCrmActivityPriority::Low;
		}

		switch ($taskPriority)
		{
			case \CTasks::PRIORITY_HIGH:
				return \CCrmActivityPriority::High;

			case \CTasks::PRIORITY_AVERAGE:
			default:
				return \CCrmActivityPriority::Low;
		}
	}
}
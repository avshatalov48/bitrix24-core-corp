<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Fields\Priority;

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
			case Priority::HIGH:
				return \CCrmActivityPriority::High;

			case Priority::LOW:
			default:
				return \CCrmActivityPriority::Low;

		}
	}
}
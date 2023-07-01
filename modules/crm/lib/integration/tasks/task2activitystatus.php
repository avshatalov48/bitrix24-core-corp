<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Crm\Activity\Provider\Tasks\TaskActivityStatus;
use Bitrix\Main\Loader;

class Task2ActivityStatus
{
	public static function getStatus(int $taskStatus): string
	{
		if (!Loader::includeModule('tasks'))
		{
			return TaskActivityStatus::STATUS_UPDATED;
		}

		switch ($taskStatus)
		{
			case TaskActivityStatus::TASKS_STATE_COMPLETED:
				return TaskActivityStatus::STATUS_FINISHED;

			case TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED:
				return TaskActivityStatus::STATUS_CONTROL_WAITING;

			default:
				return TaskActivityStatus::STATUS_UPDATED;

		}
	}
}
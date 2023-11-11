<?php

namespace Bitrix\Tasks\UI\Task;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/lib/grid/task/row/content/status.php');

class Status
{
	public static function getList(): array
	{
		return [
			\Bitrix\Tasks\Internals\Task\Status::NEW => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\Bitrix\Tasks\Internals\Task\Status::NEW),
			\Bitrix\Tasks\Internals\Task\Status::PENDING => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\Bitrix\Tasks\Internals\Task\Status::PENDING),
			\Bitrix\Tasks\Internals\Task\Status::IN_PROGRESS => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'. \Bitrix\Tasks\Internals\Task\Status::IN_PROGRESS),
			\Bitrix\Tasks\Internals\Task\Status::SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\Bitrix\Tasks\Internals\Task\Status::SUPPOSEDLY_COMPLETED),
			\Bitrix\Tasks\Internals\Task\Status::COMPLETED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'. \Bitrix\Tasks\Internals\Task\Status::COMPLETED),
			\Bitrix\Tasks\Internals\Task\Status::DEFERRED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\Bitrix\Tasks\Internals\Task\Status::DEFERRED),
			\Bitrix\Tasks\Internals\Task\Status::DECLINED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\Bitrix\Tasks\Internals\Task\Status::DECLINED),
		];
	}
}
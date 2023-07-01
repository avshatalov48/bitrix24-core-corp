<?php

namespace Bitrix\Tasks\UI\Task;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/lib/grid/task/row/content/status.php');

class Status
{
	public static function getList(): array
	{
		return [
			\CTasks::STATE_NEW => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_NEW),
			\CTasks::STATE_PENDING => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_PENDING),
			\CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_IN_PROGRESS),
			\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_SUPPOSEDLY_COMPLETED),
			\CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_COMPLETED),
			\CTasks::STATE_DEFERRED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_DEFERRED),
			\CTasks::STATE_DECLINED => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.\CTasks::STATE_DECLINED),
		];
	}
}
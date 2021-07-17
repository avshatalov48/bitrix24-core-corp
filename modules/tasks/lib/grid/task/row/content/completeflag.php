<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;
use CTasks;

/**
 * Class CompleteFlag
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class CompleteFlag extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		$title = '';
		$onClick = '';
		$class = '';

		if ($row['ACTION']['COMPLETE'])
		{
			$title = GetMessageJS('TASKS_GRID_TASK_ROW_CONTENT_COMPLETE_FLAG_CLOSE_TASK');
			$onClick = "BX.Tasks.GridActions.action('complete', {$row['ID']});";
			$class = 'task-complete-action-need-complete';
		}
		else if ($row['REAL_STATUS'] === CTasks::STATE_COMPLETED)
		{
			$title = GetMessageJS('TASKS_GRID_TASK_ROW_CONTENT_COMPLETE_FLAG_FINISHED');
			$class = 'task-complete-action-completed';
		}

		if ($title !== '')
		{
			return "<a class=\"task-complete-action {$class}\" href=\"javascript:;\" title=\"{$title}\" onclick=\"{$onClick}\"></a>";
		}

		return '';
	}
}
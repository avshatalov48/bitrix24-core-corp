<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;
use CTasks;

/**
 * Class CompleteFlag
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class CompleteFlag extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$title = '';
		$onClick = '';
		$class = '';

		if ($row['ACTION']['COMPLETE'])
		{
			$title = GetMessageJS('TASKS_GRID_ROW_CONTENT_COMPLETE_FLAG_CLOSE_TASK');
			$onClick = "BX.Tasks.GridActions.action('complete', {$row['ID']});";
			$class = 'task-complete-action-need-complete';
		}
		else if ($row['REAL_STATUS'] === CTasks::STATE_COMPLETED)
		{
			$title = GetMessageJS('TASKS_GRID_ROW_CONTENT_COMPLETE_FLAG_FINISHED');
			$class = 'task-complete-action-completed';
		}

		if ($title !== '')
		{
			return "<a class=\"task-complete-action {$class}\" href=\"javascript:;\" title=\"{$title}\" onclick=\"{$onClick}\"></a>";
		}

		return '';
	}
}
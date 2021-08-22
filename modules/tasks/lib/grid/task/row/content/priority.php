<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Priority
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Priority extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		if (!array_key_exists('PRIORITY', $row))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_PRIORITY_'.$row['PRIORITY']) ?? '';
	}
}
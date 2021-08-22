<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Status
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Status extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		if (!array_key_exists('REAL_STATUS', $row))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_STATUS_'.$row['REAL_STATUS']) ?? '';
	}
}
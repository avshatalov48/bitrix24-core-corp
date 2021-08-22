<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class ChangeDeadlinePermission
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class ChangeDeadlinePermission extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		if (!array_key_exists('ALLOW_CHANGE_DEADLINE', $row) || !$this->isValid($row['ALLOW_CHANGE_DEADLINE']))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_ALLOW_CHANGE_DEADLINE_'.$row['ALLOW_CHANGE_DEADLINE']) ?? '';
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private function isValid($value): bool
	{
		return isset($value) && in_array(strtoupper($value), ['Y', 'N'], true);
	}
}
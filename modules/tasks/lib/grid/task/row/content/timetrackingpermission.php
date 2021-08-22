<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class TimeTrackingPermission
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class TimeTrackingPermission extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		if (!array_key_exists('ALLOW_TIME_TRACKING', $row) || !$this->isValid($row['ALLOW_TIME_TRACKING']))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_ALLOW_TIME_TRACKING_'.$row['ALLOW_TIME_TRACKING']) ?? '';
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
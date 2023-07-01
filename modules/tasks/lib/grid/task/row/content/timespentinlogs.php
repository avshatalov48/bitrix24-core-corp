<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class TimeSpentInLogs
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class TimeSpentInLogs extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();

		return sprintf(
			'%02d:%02d',
			floor(($row['TIME_SPENT_IN_LOGS'] ?? 0) / 3600),
			floor(($row['TIME_SPENT_IN_LOGS'] ?? 0) / 60) % 60
		);
	}
}
<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class TimeSpentInLogs
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class TimeSpentInLogs extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return sprintf(
			'%02d:%02d',
			floor(($row['TIME_SPENT_IN_LOGS'] ?: 0) / 3600),
			floor(($row['TIME_SPENT_IN_LOGS'] ?: 0) / 60) % 60
		);
	}
}
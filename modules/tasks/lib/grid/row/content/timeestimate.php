<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class TimeEstimate
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class TimeEstimate extends Content
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
			floor(($row['TIME_ESTIMATE'] ?: 0) / 3600),
			floor(($row['TIME_ESTIMATE'] ?: 0) / 60) % 60
		);
	}
}
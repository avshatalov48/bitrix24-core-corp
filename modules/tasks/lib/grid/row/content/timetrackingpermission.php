<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class TimeTrackingPermission
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class TimeTrackingPermission extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		if (!array_key_exists('ALLOW_TIME_TRACKING', $row) || !static::isValid($row['ALLOW_TIME_TRACKING']))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_ROW_CONTENT_ALLOW_TIME_TRACKING_'.$row['ALLOW_TIME_TRACKING']);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private static function isValid($value): bool
	{
		return isset($value) && in_array(strtoupper($value), ['Y', 'N'], true);
	}
}
<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class ChangeDeadlinePermission
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class ChangeDeadlinePermission extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		if (!array_key_exists('ALLOW_CHANGE_DEADLINE', $row) || !static::isValid($row['ALLOW_CHANGE_DEADLINE']))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_ROW_CONTENT_ALLOW_CHANGE_DEADLINE_'.$row['ALLOW_CHANGE_DEADLINE']);
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
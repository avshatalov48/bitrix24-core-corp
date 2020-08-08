<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class Priority
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Priority extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		if (!array_key_exists('PRIORITY', $row))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_ROW_CONTENT_PRIORITY_'.$row['PRIORITY']);
	}
}
<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class Status
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Status extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		if (!array_key_exists('REAL_STATUS', $row))
		{
			return '';
		}

		return Loc::getMessage('TASKS_GRID_ROW_CONTENT_STATUS_'.$row['REAL_STATUS']);
	}
}
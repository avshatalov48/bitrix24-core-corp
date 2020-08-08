<?php
namespace Bitrix\Tasks\Grid\Row\Content\Date;

use Bitrix\Tasks\Grid\Row\Content\Date;
use CTasks;

/**
 * Class ChangedDate
 *
 * @package Bitrix\Tasks\Grid\Row\Content\Date
 */
class ChangedDate extends Date
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return static::formatDate($row['CHANGED_DATE']);
	}
}
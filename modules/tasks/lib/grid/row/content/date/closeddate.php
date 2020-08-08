<?php
namespace Bitrix\Tasks\Grid\Row\Content\Date;

use Bitrix\Tasks\Grid\Row\Content\Date;

/**
 * Class ClosedDate
 *
 * @package Bitrix\Tasks\Grid\Row\Content\Date
 */
class ClosedDate extends Date
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return static::formatDate($row['CLOSED_DATE']);
	}
}
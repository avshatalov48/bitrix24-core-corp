<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class Id
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class TaskId extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return (string)$row['ID'];
	}
}
<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class ParentId
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class ParentId extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return (string)$row['PARENT_ID'];
	}
}
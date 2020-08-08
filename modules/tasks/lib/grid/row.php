<?php
namespace Bitrix\Tasks\Grid;

use Bitrix\Main;

/**
 * Class GridRow
 *
 * @package Bitrix\Tasks\TaskList
 */
class Row
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return array|array[]
	 * @throws Main\LoaderException
	 */
	public static function prepareActions(array $row, array $parameters): array
	{
		return Row\Action::prepare($row, $parameters);
	}

	/**
	 * @param array $row
	 * @param array $parameters
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\ArgumentException
	 */
	public static function prepareContent(array $row, array $parameters): array
	{
		return Row\Content::prepare($row, $parameters);
	}
}
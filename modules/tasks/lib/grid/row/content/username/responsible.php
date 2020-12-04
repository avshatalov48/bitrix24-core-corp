<?php
namespace Bitrix\Tasks\Grid\Row\Content\UserName;

use Bitrix\Main;
use Bitrix\Tasks\Grid\Row\Content\UserName;

/**
 * Class Responsible
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Responsible extends UserName
{
	protected const USER_ROLE = 'RESPONSIBLE_ID';

	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public static function prepare(array $row, array $parameters): string
	{
		return static::prepareUserName($row, $parameters);
	}
}
<?php
namespace Bitrix\Tasks\Grid\Row\Content\UserName;

use Bitrix\Main;
use Bitrix\Tasks\Grid\Row\Content\UserName;

/**
 * Class Originator
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Originator extends UserName
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$userRole = 'CREATED_BY';

		return static::prepareUserName([
			'USER_ROLE' => $userRole,
			'USER_ID' => $row[$userRole],
		]);
	}
}
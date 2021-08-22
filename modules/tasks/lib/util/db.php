<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Util;


class Db
{

	/**
	 * @param $strValue
	 * @param string $strType
	 * @param false $lang
	 * @return string
	 */
	public static function charToDateFunction($strValue, $strType = "FULL", $lang = false): string
	{
		global $DB;

		$sql = $DB->CharToDateFunction($strValue, $strType, $lang);

		if ($sql === "''")
		{
			$sql = '0';
		}

		return $sql;
	}

}
<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\Main\Application;

/**
 * Class DbNameLock
 * @package Bitrix\ImOpenLines
 */
class DbNameLock
{
	/**
	 * @return \Bitrix\Main\DB\Connection
	 */
	protected static function getConnection()
	{
		return Application::getConnection();
	}

	/**
	 * @return string
	 */
	protected static function getUniqId()
	{
		return \CMain::GetServerUniqID();
	}

	/**
	 * @param $name
	 * @return string
	 */
	protected static function getName($name)
	{
		return self::getUniqId() . '_imol_' . $name;
	}

	/**
	 * @param $name
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function isFree($name)
	{
		return !empty(self::getConnection()->query("SELECT IS_FREE_LOCK('" . self::getName($name) . "') AS RESULT")->fetch()['RESULT']);
	}

	/**
	 * @param $name
	 * @param int $time
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function set($name, $time = 0)
	{
		$result = false;

		if(self::isFree($name))
		{
			$result = !empty(self::getConnection()->query("SELECT GET_LOCK('" . self::getName($name) . "', $time) AS RESULT")->fetch()['RESULT']);
		}

		return $result;
	}

	/**
	 * @param $name
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function delete($name)
	{
		return !empty(self::getConnection()->query("SELECT RELEASE_LOCK('" . self::getName($name) . "') AS RESULT")->fetch()['RESULT']);
	}
}
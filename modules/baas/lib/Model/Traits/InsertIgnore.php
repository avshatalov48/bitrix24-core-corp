<?php

namespace Bitrix\Baas\Model\Traits;

use \Bitrix\Main;

trait InsertIgnore
{
	public static function insertIgnore(array $fields): int
	{
		$tableName = static::getTableName();
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insert = $sqlHelper->prepareInsert($tableName, $fields);
		$strSql = $sqlHelper->getInsertIgnore(
			$tableName,
			"(" . $insert[0] . ") ",
			"VALUES (" . $insert[1] . ")",
		);
		$connection->queryExecute($strSql, $insert[2]);
		static::cleanCache();

		return $connection->getInsertedId();
	}
}

<?php

namespace Bitrix\Crm;

use Bitrix\Main\Application;
use Bitrix\Main\DB\PgsqlConnection;

class DbHelper
{
	public static function queryByDbType(
		string $mysqlQuery,
		string $pgsqlQuery
	)
	{
		$connection = Application::getConnection();
		$query = self::getSqlByDbType($mysqlQuery, $pgsqlQuery);

		return $connection->query($query);
	}

	public static function getSqlByDbType(
		string $mysqlQuery,
		string $pgsqlQuery
	): string
	{
		return self::isPgSqlDb() ? $pgsqlQuery : $mysqlQuery;
	}

	public static function isPgSqlDb(): bool
	{
		$connection = Application::getConnection();

		return ($connection instanceof PgsqlConnection);
	}
}

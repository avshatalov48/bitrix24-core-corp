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
		$query = $connection instanceof PgsqlConnection ? $pgsqlQuery : $mysqlQuery;

		return $connection->query($query);
	}
}

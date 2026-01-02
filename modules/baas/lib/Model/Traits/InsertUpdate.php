<?php

namespace Bitrix\Baas\Model\Traits;

use \Bitrix\Main;

trait InsertUpdate
{
	public static function insertUpdate(array $insertFields, array $updateFields): void
	{
		$tableName = static::getTableName();
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sqls = $sqlHelper->prepareMerge(
			$tableName,
			static::getEntity()->getPrimaryArray(),
			$insertFields,
			$updateFields,
		);

		foreach ($sqls as $sql)
		{
			$connection->queryExecute($sql);
		}

		static::cleanCache();
	}
}

<?php

namespace Bitrix\Disk\Internals\Db;

use Bitrix\Main\Application;

final class SqlHelper
{
	const MAX_LENGTH_BATCH_MYSQL_QUERY = 2048;

	public static function insertBatch($tableName, array $items)
	{
		if (!$items)
		{
			return;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';
		foreach ($items as $item)
		{
			list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

			$query .= ($query ? ', ' : ' ') . '(' . $values . ')';
			if (mb_strlen($query) > self::MAX_LENGTH_BATCH_MYSQL_QUERY)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
				$query = '';
			}
		}

		if ($query && $prefix)
		{
			$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
		}
	}

	public static function upsertBatch($tableName, array $items, array $updateFields)
	{
		if (!$items)
		{
			return;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';
		list($update) = $sqlHelper->prepareUpdate($tableName, $updateFields);

		foreach ($items as $item)
		{
			list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

			$query .= ($query ? ', ' : ' ') . '(' . $values . ')';
			if (mb_strlen($query) > self::MAX_LENGTH_BATCH_MYSQL_QUERY)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query} ON DUPLICATE KEY UPDATE {$update}");
				$query = '';
			}
		}

		if ($query && $prefix && $update)
		{
			$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query} ON DUPLICATE KEY UPDATE {$update}");
		}
	}
}
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

	public static function upsertBatch(string $tableName, array $primaryFields, array $items, array $updateFields): void
	{
		if (!$items)
		{
			return;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$tableFields = $connection->getTableFields($tableName);

		$query = '';
		foreach ($items as $item)
		{
			$item = array_change_key_case($item, CASE_UPPER);

			$columns = [];
			$values = [];
			foreach ($item as $columnName => $value)
			{
				if (isset($tableFields[$columnName]))
				{
					$columns[] = $columnName;
					$values[] = $sqlHelper->convertToDb($value, $tableFields[$columnName]);
				}
			}

			$query .= ($query ? ', ' : ' ') . '(' . implode(', ', $values) . ')';
			if (mb_strlen($query) > self::MAX_LENGTH_BATCH_MYSQL_QUERY)
			{
				$sql = $sqlHelper->prepareMergeSelect(
					$tableName,
					$primaryFields,
					$columns,
					" VALUES {$query} ",
					$updateFields
				);
				$connection->queryExecute($sql);

				$query = '';
			}
		}

		if ($query && $columns)
		{
			$sql = $sqlHelper->prepareMergeSelect(
				$tableName,
				$primaryFields,
				$columns,
				" VALUES {$query} ",
				$updateFields
			);
			$connection->queryExecute($sql);
		}
	}
}
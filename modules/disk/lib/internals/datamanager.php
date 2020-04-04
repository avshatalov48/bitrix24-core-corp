<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Internals\Db\SqlHelper;
use Bitrix\Disk\Internals\Entity\Query;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;

abstract class DataManager extends \Bitrix\Main\Entity\DataManager
{
	const MAX_LENGTH_BATCH_MYSQL_QUERY = SqlHelper::MAX_LENGTH_BATCH_MYSQL_QUERY;

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Creates and returns the Query object for the entity
	 *
	 * @return Query
	 */
	public static function query()
	{
		return new Query(static::getEntity());
	}

	public static function mergeData(array $insert, array $update)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		$sql = $helper->prepareMerge($entity->getDBTableName(), $entity->getPrimaryArray(), $insert, $update);

		$sql = current($sql);
		if($sql <> '')
		{
			$connection->queryExecute($sql);
			$entity->cleanCache();
		}
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return bool
	 */
	public static function deleteByFilter(array $filter)
	{
		if (!$filter)
		{
			throw new ArgumentNullException('filter');
		}

		$result = static::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
		));
		while($row = $result->fetch())
		{
			if(!empty($row['ID']))
			{
				$resultDelete = static::delete($row['ID']);
				if(!$resultDelete->isSuccess())
				{
					return false;
				}
			}
		}
		//todo? Return new DbResult with lists of deleted object?
		return true;
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	protected static function insertBatch(array $items)
	{
		SqlHelper::insertBatch(static::getTableName(), $items);
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 */
	protected static function deleteBatch(array $filter)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$where = [];
		foreach ($filter as $key => $value)
		{
			$where[] = $helper->prepareAssignment($tableName, $key, $value);
		}
		$where = implode(' AND ', $where);

		if($where)
		{
			$quotedTableName = $helper->quote($tableName);
			$connection->queryExecute("DELETE FROM {$quotedTableName} WHERE {$where}");
		}
	}

	/**
	 * Updates rows by filter (simple format).
	 * @param array $fields Fields.
	 * @param array $filter Filter.
	 */
	protected static function updateBatch(array $fields, array $filter)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($tableName, $fields);

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'UPDATE ' . $tableName . ' SET ' . $update[0] . ' WHERE ' . $where;
		$connection->queryExecute($sql, $update[1]);
	}
}
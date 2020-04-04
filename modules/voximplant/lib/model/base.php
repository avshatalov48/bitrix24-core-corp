<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;
use Bitrix\Voximplant\Internals\Entity\Query;

abstract class Base extends ORM\Data\DataManager
{
	/**
	 * Deletes all records from the table.
	 * @return null
	 */
	public static function truncate()
	{
		$helper = Application::getConnection()->getSqlHelper();
		$sql = "TRUNCATE ".$helper->quote(static::getTableName());

		return Application::getConnection()->query($sql);
	}

	/**
	 * Just updates database record. Without events, validators, modifiers, etc.
	 * @param int $id Id of the record.
	 * @param array $fields Fields to be updated.
	 */
	public static function simpleUpdate($id, array $fields)
	{
		$id = (int)$id;
		if($id == 0)
			return;

		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$update = $helper->prepareUpdate(static::getTableName(), $fields);
		$query = 'UPDATE '. $helper->quote(static::getTableName()) . ' SET ' . $update[0] . ' WHERE ID = ' . $id;
		$conn->queryExecute($query);
	}

	/**
	 * Simply updates rows using filter.
	 * @param array $fields Fields.
	 * @param array $filter Filter.
	 * @param int $limit Limit.
	 * @return int Returns count of affected rows.
	 */
	public static function updateBatch(array $fields, array $filter, $limit = 0)
	{
		$limit = (int)$limit;
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
		if($limit > 0)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		$connection->queryExecute($sql, $update[1]);
		return $connection->getAffectedRowsCount();
	}

	/**
	 * Deletes rows using filter.
	 * @param array $filter Filter.
	 * @param int $limit Limit.
	 * @return int Returns count of affected rows.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteBatch(array $filter, $limit = 0)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		if($limit > 0)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		$connection->queryExecute($sql);
		return $connection->getAffectedRowsCount();
	}

	/**
	 * Inserts new record into the table, or updates existing record, if record is already found in the table.
	 *
	 * @param array $data Record to be merged to the table.
	 * @return Entity\AddResult
	 */
	public static function merge(array $data)
	{
		$result = new Entity\AddResult();

		$helper = Application::getConnection()->getSqlHelper();
		$insertData = $data;
		$updateData = $data;
		$mergeFields = static::getMergeFields();
		foreach ($mergeFields as $field)
		{
			unset($updateData[$field]);
		}
		$merge = $helper->prepareMerge(
			static::getTableName(),
			static::getMergeFields(),
			$insertData,
			$updateData
		);

		if ($merge[0] != "")
		{
			Application::getConnection()->query($merge[0]);
			$id = Application::getConnection()->getInsertedId();
			$result->setId($id);
			$result->setData($data);
		}
		else
		{
			$result->addError(new Error('Error constructing query'));
		}

		return $result;
	}

	/**
	 * Should return array of names of fields, that should be used to detect record duplication.
	 * @return array;
	 */
	protected static function getMergeFields()
	{
		throw new SystemException("Method should be implemented in class " . get_called_class());
	}
}
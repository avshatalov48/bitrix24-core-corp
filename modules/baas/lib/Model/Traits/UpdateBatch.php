<?php

namespace Bitrix\Baas\Model\Traits;

use \Bitrix\Main;

trait UpdateBatch
{
	public static function updateBatch(array $fields, array $filter): Main\ORM\Data\UpdateResult
	{
		$result = new Main\ORM\Data\UpdateResult();
		$tableName = static::getTableName();
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($tableName, $fields);
		$where = Main\ORM\Query\Query::buildFilterSql(static::getEntity(), $filter);

		$sql = 'UPDATE ' . $tableName . ' SET ' . $update[0] . ' WHERE ' . $where;

		$connection->queryExecute($sql, $update[1]);

		$result->setAffectedRowsCount($connection);

		static::cleanCache();

		return $result;
	}
}

<?php

namespace Bitrix\Baas\Model\Traits;

use \Bitrix\Main;

trait DeleteBatch
{
	// public const EVENT_ON_AFTER_DELETE_BATCH = "OnAfterDeleteBatch";

	public static function deleteBatch(array $filter)
	{
		$whereSql = Main\Entity\Query::buildFilterSql(static::getEntity(), $filter);

		if ($whereSql <> '')
		{
			$entity = static::getEntity();
			$tableName = static::getTableName();
			$connection = Main\Application::getConnection();
			$connection->queryExecute("DELETE FROM {$tableName} WHERE {$whereSql}");

			static::cleanCache();
			static::callOnAfterDeleteBatchEvent($filter, $entity);
		}

		return new Main\Entity\DeleteResult();
	}

	/**
	 * @param $filter
	 * @param $entity
	 */
	protected static function callOnAfterDeleteBatchEvent($filter, $entity)
	{
		// $event = new Main\ORM\Event($entity, self::EVENT_ON_AFTER_DELETE_BATCH, ["filter" => $filter], true);
		// $event->send();
	}
}

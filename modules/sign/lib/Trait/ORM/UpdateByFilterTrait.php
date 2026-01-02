<?php

namespace Bitrix\Sign\Trait\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Query\Query;

trait UpdateByFilterTrait
{
	/**
	 * @param array $filter
	 * @param array $fields
	 */
	public static function updateByFilter(array $filter, array $fields): void
	{
		if (!is_subclass_of(static::class, DataManager::class))
		{
			throw new \Bitrix\Main\SystemException('Class ' . static::class . ' must be subclass of ' . DataManager::class);
		}

		$entity = static::getEntity();
		$sqlTableName = static::getTableName();
		$sqlHelper = $entity->getConnection()->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($sqlTableName, $fields);
		$where = Query::buildFilterSql($entity, $filter);
		if ($where !== '' && $update[0] !== '')
		{
			$sql = "UPDATE {$sqlTableName} SET {$update[0]} WHERE {$where}";
			$entity->getConnection()->queryExecute($sql);
		}
		static::cleanCache();
	}
}
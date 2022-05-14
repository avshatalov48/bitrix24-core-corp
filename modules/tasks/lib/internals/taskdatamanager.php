<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Query\Query;

class TaskDataManager extends DataManager
{
	/**
	 * @param array $filter
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteList(array $filter)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		$enabled = \CTimeZone::Enabled();
		if ($enabled)
		{
			\CTimeZone::Disable();
		}

		$sql = sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		);
		$res = $connection->query($sql);

		if ($enabled)
		{
			\CTimeZone::Enable();
		}

		return $res;
	}
}
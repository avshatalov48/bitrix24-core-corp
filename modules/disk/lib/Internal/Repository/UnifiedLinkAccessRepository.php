<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Repository;

use Bitrix\Disk\Internal\
{Model\UnifiedLinkAccessTable};
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class UnifiedLinkAccessRepository
{
	/**
	 * @param int $objectId
	 * @return UnifiedLinkAccessLevel|null
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public static function getByObjectId(int $objectId): ?UnifiedLinkAccessLevel
	{
		$result = UnifiedLinkAccessTable::query()
			->setSelect(['ACCESS_LEVEL'])
			->where('OBJECT_ID', $objectId)
			->setLimit(1)
			->fetch()
		;

		if (isset($result['ACCESS_LEVEL']) && is_string($result['ACCESS_LEVEL']))
		{
			return UnifiedLinkAccessLevel::tryFrom($result['ACCESS_LEVEL']);
		}

		return null;
	}

	/**
	 * @param int $objectId
	 * @param UnifiedLinkAccessLevel $level
	 * @return AddResult
	 */
	public static function set(int $objectId, UnifiedLinkAccessLevel $level): AddResult
	{
		return UnifiedLinkAccessTable::addMerge([
			'OBJECT_ID' => $objectId,
			'ACCESS_LEVEL' => $level->value,
		]);
	}

	/**
	 * @param int $objectId
	 * @return void
	 * @throws SqlQueryException
	 */
	public static function deleteByObjectId(int $objectId): void
	{
		$connection = Application::getConnection();
		$tableName = UnifiedLinkAccessTable::getTableName();

		$connection->queryExecute("DELETE FROM {$tableName} WHERE OBJECT_ID = {$objectId}");
	}
}
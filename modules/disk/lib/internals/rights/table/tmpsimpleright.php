<?php
namespace Bitrix\Disk\Internals\Rights\Table;

use Bitrix\Disk\Internals\Db\SqlHelper;
use Bitrix\Disk\Internals\ObjectPathTable;
use Bitrix\Main\Application;

final class TmpSimpleRight
{
	public static function getTableName()
	{
		return 'b_disk_tmp_simple_right';
	}

	/**
	 * Adds rows to table.
	 *
	 * @param array $items Items.
	 * @param int $sessionId
	 *
	 * @internal
	 */
	public static function insertBatchBySessionId(array $items, $sessionId)
	{
		foreach ($items as &$item)
		{
			$item['SESSION_ID'] = $sessionId;
		}
		unset($item);

		SqlHelper::insertBatch(self::getTableName(), $items);
	}

	/**
	 * Fills descendants simple rights by simple rights of object
	 * @internal
	 * @param int $objectId Id of object.
	 */
	public static function fillDescendants($objectId, $sessionId)
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$pathTableName = $helper->quote(ObjectPathTable::getTableName());
		$sessionId = (int)$sessionId;
		$objectId = (int)$objectId;

		$sql = $helper->getInsertIgnore(
			'b_disk_tmp_simple_right',
			' (OBJECT_ID, ACCESS_CODE, SESSION_ID) ',
			"SELECT path.OBJECT_ID, sright.ACCESS_CODE, {$sessionId} 
				FROM {$pathTableName} path INNER JOIN b_disk_tmp_simple_right sright ON sright.OBJECT_ID = path.PARENT_ID
				WHERE path.PARENT_ID = {$objectId} AND sright.SESSION_ID = {$sessionId}"
		);
		$connection->queryExecute($sql);
	}

	public static function moveToOriginalSimpleRights($sessionId)
	{
		$connection = Application::getConnection();

		$connection->queryExecute("
			INSERT INTO b_disk_simple_right (OBJECT_ID, ACCESS_CODE)
			SELECT tmp_right.OBJECT_ID, tmp_right.ACCESS_CODE FROM b_disk_tmp_simple_right tmp_right
			WHERE tmp_right.SESSION_ID = {$sessionId}
		");
	}

	public static function deleteBySessionId($sessionId)
	{
		$sessionId = (int)$sessionId;
		$connection = Application::getConnection();

		$connection->queryExecute("
			DELETE FROM b_disk_tmp_simple_right WHERE SESSION_ID = {$sessionId}
		");
	}
}
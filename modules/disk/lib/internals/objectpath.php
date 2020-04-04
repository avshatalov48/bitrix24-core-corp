<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\Collection;

/**
 * Class ObjectPathTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PARENT_ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> DEPTH_LEVEL int optional
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class ObjectPathTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_object_path';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
			),
			'DEPTH_LEVEL' => array(
				'data_type' => 'integer',
			),
		);
	}

	/**
	 * Create root in closure.
	 * @param $objectId
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 * @throws \Exception
	 * @return Entity\AddResult
	 */
	public static function addAsRoot($objectId)
	{
		$objectId = (int)$objectId;

		return static::add(array(
			'PARENT_ID' => $objectId,
			'OBJECT_ID' => $objectId,
			'DEPTH_LEVEL' => 0,
		));
	}

	/**
	 * Append target to node,
	 * @param $objectId
	 * @param $appendToNodeId
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function appendTo($objectId, $appendToNodeId)
	{
		$objectId = (int)$objectId;
		$appendToNodeId = (int)$appendToNodeId;

		$table = static::getTableName();
		$sql = "
			INSERT INTO {$table} (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
			SELECT PARENT_ID, {$objectId}, DEPTH_LEVEL+1 FROM {$table} WHERE OBJECT_ID = {$appendToNodeId}
			UNION ALL SELECT {$objectId}, {$objectId}, 0
		";

		//todo? return nothing? Or return AddResult? GetId?
		$connection = Application::getInstance()->getConnection();
		$connection->queryExecute($sql);
	}

	/**
	 * Action move object to another node.
	 * Recalculated paths.
	 * @param $objectId
	 * @param $toNodeId
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function moveTo($objectId, $toNodeId)
	{
		$objectId = (int)$objectId;
		$toNodeId = (int)$toNodeId;

		$connection = Application::getInstance()->getConnection();
		$table = static::getTableName();

		$sql = "
			DELETE a FROM {$table} a
				JOIN {$table} d
					ON a.OBJECT_ID = d.OBJECT_ID
				LEFT JOIN {$table} x
					ON x.PARENT_ID = d.PARENT_ID AND x.OBJECT_ID = a.PARENT_ID
				WHERE d.PARENT_ID = {$objectId} AND x.PARENT_ID IS NULL
		";

		$connection->queryExecute($sql);
		
		$sql = "
			INSERT INTO {$table} (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
				SELECT stree.PARENT_ID, subtree.OBJECT_ID, stree.DEPTH_LEVEL+subtree.DEPTH_LEVEL+1
				FROM {$table} stree JOIN {$table} subtree ON subtree.PARENT_ID = {$objectId} AND stree.OBJECT_ID = {$toNodeId}
		";

		$connection->queryExecute($sql);
	}

	/**
	 * Get descendants objects of $objectId.
	 * PARENT_ID not really! Be careful :)
	 * @param     $objectId
	 * @param int $orderDepthLevel SORT_ASC | SORT_DESC
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 * @return array
	 */
	public static function getDescendants($objectId, $orderDepthLevel = SORT_ASC)
	{
		$objectId = (int)$objectId;

		$objectPaths = static::getList(array(
			'select' => array('ID', 'PARENT_ID', 'OBJECT_ID', 'DEPTH_LEVEL'),
			'filter' => array(
				'PARENT_ID' => $objectId,
				'!OBJECT_ID' => $objectId,
			),
		))->fetchAll();

		Collection::sortByColumn($objectPaths, array('DEPTH_LEVEL' => $orderDepthLevel));

		return $objectPaths;
	}

	/**
	 * Get direct children objects of $objectId.
	 * @param $objectId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getChildren($objectId)
	{
		$objectId = (int)$objectId;

		$objectPaths = static::getList(array(
			'select' => array('ID', 'PARENT_ID', 'OBJECT_ID', 'DEPTH_LEVEL'),
			'filter' => array(
				'PARENT_ID' => $objectId,
				'DEPTH_LEVEL' => 1,
			),
		))->fetchAll();

		return $objectPaths;
	}

	/**
	 * Get ancestors objects of $objectId.
	 * PARENT_ID not really! Be careful :)
	 * @param     $objectId
	 * @param int $orderDepthLevel SORT_ASC | SORT_DESC
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 * @return array
	 */
	public static function getAncestors($objectId, $orderDepthLevel = SORT_ASC)
	{
		$objectId = (int)$objectId;

		$objectPaths = static::getList(array(
			'select' => array('ID', 'PARENT_ID', 'OBJECT_ID', 'DEPTH_LEVEL'),
			'filter' => array(
				'OBJECT_ID' => $objectId,
				'!PARENT_ID' => $objectId,
			),
		))->fetchAll();

		Collection::sortByColumn($objectPaths, array('DEPTH_LEVEL' => $orderDepthLevel));

		return $objectPaths;
	}

	public static function deleteByObject($objectId)
	{
		//todo Is leaf? If this is not leaf - throw Exception?
		$objectId = (int)$objectId;

		$table = static::getTableName();
		$sql = "DELETE FROM {$table} WHERE OBJECT_ID = {$objectId}";

		Application::getInstance()->getConnection()->queryExecute($sql);
	}

	/**
	 * Object is leaf?
	 * @param $objectId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 * @return bool
	 */
	public static function isLeaf($objectId)
	{
		$objectId = (int)$objectId;

		$paths = static::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'!OBJECT_ID' => $objectId,
				'PARENT_ID' => $objectId,
			),
			'limit' => 1,
		))->fetch();

		return empty($paths);
	}

	/**
	 * Recalculate object paths by parentId in Object.
	 * Uses inner joins to find relations, without recursive walks by parentId.
	 * Attention! This method truncate table b_disk_object_path and recalculates them.
	 * @internal
	 */
	public static function recalculate()
	{
		set_time_limit(0);
		$maxInnerJoinDepth = 32;
		$currentDepth = 0;
		$emptyInsert = false;
		$connection = Application::getConnection();

		$connection->queryExecute("TRUNCATE TABLE b_disk_object_path");
		$connection->queryExecute("
			INSERT INTO b_disk_object_path (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
			SELECT ID, ID, 0 FROM b_disk_object
		");

		while($currentDepth < $maxInnerJoinDepth && !$emptyInsert)
		{
			$query = "
				INSERT INTO b_disk_object_path (OBJECT_ID, PARENT_ID, DEPTH_LEVEL)
					SELECT b.ID, t.ID, " . ($currentDepth+1) . " FROM b_disk_object t
			";

			$finalQuery = $query;
			for($i = 0;$i < $currentDepth;$i++)
			{
				$finalQuery .= " INNER JOIN b_disk_object t" . ($i+1) . " ON t" . ($i?: '') . ".ID=t" . ($i+1) . ".PARENT_ID ";

			}
			$lastJoin = " INNER JOIN b_disk_object b ON t" . ($currentDepth?:'' ) . ".ID=b.PARENT_ID ";
			$finalQuery = $finalQuery .$lastJoin;

			$connection->queryExecute($finalQuery);
			$emptyInsert = $connection->getAffectedRowsCount() <= 0;

			$currentDepth++;
		}
	}

	/**
	 * Recalculates paths for the storage.
	 *
	 * @param int $storageId Storage id.
	 * @throws ArgumentOutOfRangeException
	 * @return void
	 */
	public static function recalculateByStorage($storageId)
	{
		$storageId = (int)$storageId;
		if ($storageId <= 0)
		{
			throw new ArgumentOutOfRangeException('storageId');
		}

		set_time_limit(0);
		$maxInnerJoinDepth = 32;
		$currentDepth = 0;
		$emptyInsert = false;
		$connection = Application::getConnection();

		$connection->queryExecute("
			DELETE p
			FROM b_disk_object_path p
			INNER JOIN b_disk_object object ON object.ID = p.OBJECT_ID
			WHERE object.STORAGE_ID = {$storageId}
		");
		$connection->queryExecute("
			DELETE p
			FROM b_disk_object_path p
			INNER JOIN b_disk_object object_p ON object_p.ID = p.PARENT_ID
			WHERE object_p.STORAGE_ID = {$storageId}
		");

		$connection->queryExecute("
			INSERT INTO b_disk_object_path (PARENT_ID, OBJECT_ID, DEPTH_LEVEL)
			SELECT ID, ID, 0 FROM b_disk_object WHERE STORAGE_ID = {$storageId}
		");

		while($currentDepth < $maxInnerJoinDepth && !$emptyInsert)
		{
			$query = "
				INSERT INTO b_disk_object_path (OBJECT_ID, PARENT_ID, DEPTH_LEVEL)
					SELECT b.ID, t.ID, " . ($currentDepth+1) . " FROM b_disk_object t
			";

			$finalQuery = $query;
			for($i = 0;$i < $currentDepth;$i++)
			{
				$finalQuery .= " INNER JOIN b_disk_object t" . ($i+1) . " ON t" . ($i?: '') . ".ID=t" . ($i+1) . ".PARENT_ID ";

			}
			$lastJoin = " INNER JOIN b_disk_object b ON t" . ($currentDepth?:'' ) . ".ID=b.PARENT_ID ";
			$finalQuery = $finalQuery . $lastJoin . " WHERE t.STORAGE_ID = {$storageId}";

			$connection->queryExecute($finalQuery);
			$emptyInsert = $connection->getAffectedRowsCount() <= 0;

			$currentDepth++;
		}
	}
}

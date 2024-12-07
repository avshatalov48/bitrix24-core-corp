<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodePath_Query query()
 * @method static EO_NodePath_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodePath_Result getById($id)
 * @method static EO_NodePath_Result getList(array $parameters = [])
 * @method static EO_NodePath_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodePath createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodePathCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodePath wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodePathCollection wakeUpCollection($rows)
 */
class NodePathTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodePath::class;
	}

	public static function getCollectionClass(): string
	{
		return NodePathCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_path';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('PARENT_ID'))
				->configureTitle('Parent id')
			,
			(new ORM\Fields\IntegerField('CHILD_ID'))
				->configureTitle('Child id')
			,
			(new ORM\Fields\IntegerField('DEPTH'))
				->configureTitle('Including level')
			,
			(new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'CHILD_NODE',
				NodeTable::class,
				Join::on('this.CHILD_ID', 'ref.ID')
			))
			,
			(new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'PARENT_NODE',
				NodeTable::class,
				Join::on('this.PARENT_ID', 'ref.ID')
			))
			,
		];
	}

	public static function deleteList(array $filter)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		return $connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}

	public static function deleteListByStructureId(int $structureId)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$tableName = $connection->getSqlHelper()
			->quote($entity->getDbTableName());
		$nodeTableName = $connection->getSqlHelper()
			->quote(NodeTable::getTableName());

		if ($connection->getType() === 'mysql')
		{
			$sql = "DELETE $tableName FROM $tableName 
            INNER JOIN $nodeTableName ON $tableName.CHILD_ID = $nodeTableName.ID 
            WHERE $nodeTableName.STRUCTURE_ID = $structureId";
			$connection->query($sql);

			$sql = "DELETE $tableName FROM $tableName
            INNER JOIN $nodeTableName ON $tableName.PARENT_ID = $nodeTableName.ID
            WHERE $nodeTableName.STRUCTURE_ID = $structureId";
			$connection->query($sql);

			return;
		}

		$sql = "DELETE FROM $tableName WHERE CHILD_ID IN (
		SELECT ID FROM $nodeTableName WHERE STRUCTURE_ID = $structureId);";
		$connection->query($sql);

		$sql = "DELETE FROM $tableName WHERE PARENT_ID IN (
		SELECT ID FROM $nodeTableName WHERE STRUCTURE_ID = $structureId);";
		$connection->query($sql);
	}

	/**
	 * @param int $nodeId
	 * @param int $targetNodeId
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function moveWithSubtree(int $nodeId, int $targetNodeId): void
	{
		$nodeTable = self::getTableName();
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		$query = <<<SQL
DELETE FROM $nodeTable 
WHERE CHILD_ID IN (
    SELECT CHILD_ID
    FROM $nodeTable
    WHERE PARENT_ID = $nodeId
) AND PARENT_ID NOT IN (
    SELECT CHILD_ID
    FROM $nodeTable
    WHERE PARENT_ID = $nodeId
);
SQL;
		if ($connection->getType() === 'mysql')
		{
			$query = <<<SQL
DELETE p.* 
FROM $nodeTable p
JOIN (
    SELECT CHILD_ID
    FROM $nodeTable
    WHERE PARENT_ID = $nodeId
) AS c
ON p.CHILD_ID = c.CHILD_ID
LEFT JOIN (
    SELECT CHILD_ID
    FROM $nodeTable
    WHERE PARENT_ID = $nodeId
) AS parents
ON p.PARENT_ID = parents.CHILD_ID
WHERE parents.CHILD_ID IS NULL
SQL;
		}

		$connection->queryExecute($query);

		$list = <<<SQL
SELECT new_parent.PARENT_ID, child.CHILD_ID, new_parent.DEPTH + child.DEPTH AS depth
FROM (
    SELECT PARENT_ID, (DEPTH + 1) as DEPTH
    FROM $nodeTable
    WHERE CHILD_ID = $targetNodeId
    UNION ALL
    SELECT $nodeId as PARENT_ID, 0 as depth
) AS new_parent
JOIN (
    SELECT CHILD_ID, DEPTH
    FROM $nodeTable
    WHERE PARENT_ID = $nodeId OR CHILD_ID = $nodeId
) AS child
ON new_parent.PARENT_ID != child.CHILD_ID
SQL;

		$query = $helper->getInsertIgnore(
			$nodeTable,
			'(PARENT_ID, CHILD_ID, DEPTH) ',
			$list
		);

		$connection->queryExecute($query);
	}

	/**
	 * @param int|null $nodeId
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function createRootNode(?int $nodeId): void
	{
		try
		{
			self::add([
				'PARENT_ID' => $nodeId,
				'CHILD_ID' => $nodeId,
				'DEPTH' => 0,
			]);
		}
		catch (SqlQueryException $exception)
		{
		}
	}

	/**
	 * @param int $nodeId
	 * @param int|null $targetNodeId
	 *
	 * @throws SqlQueryException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function appendNode(int $nodeId, ?int $targetNodeId): void
	{
		$nodeTable = self::getTableName();
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		$targetNodeId = (int)$targetNodeId;

		$list = <<<SQL
SELECT PARENT_ID, $nodeId, DEPTH + 1 FROM $nodeTable
WHERE CHILD_ID = $targetNodeId
UNION ALL SELECT $nodeId, $nodeId, 0
SQL;

		$query = $helper->getInsertIgnore(
			$nodeTable,
			'(PARENT_ID, CHILD_ID, DEPTH) ',
			$list,
		);

		$connection->queryExecute($query);
	}

	public static function recalculate(int $structureId)
	{
		set_time_limit(0);
		$maxInnerJoinDepth = 64;
		$currentDepth = 0;
		$emptyInsert = false;

		self::deleteListByStructureId($structureId);
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();
		$nodeTable = NodeTable::getTableName();
		$nodePathTable = self::getTableName();

		$rootItem = <<<SQL
SELECT ID, ID, 0 FROM $nodeTable WHERE STRUCTURE_ID = $structureId
SQL;
		$query = $helper->getInsertIgnore(
			$nodePathTable,
			'(PARENT_ID, CHILD_ID, DEPTH) ',
			$rootItem,
		);

		$connection->queryExecute($query);
		while ($currentDepth < $maxInnerJoinDepth && !$emptyInsert)
		{
			$query = "
					SELECT b.ID, t.ID, ".($currentDepth + 1)." FROM $nodeTable t
			";

			$finalQuery = $query;
			for ($i = 0; $i < $currentDepth; $i++)
			{
				$finalQuery .= " INNER JOIN $nodeTable t".($i + 1)." ON t".($i ? : '').".ID=t".($i + 1).".PARENT_ID ";

			}
			$lastJoin = " INNER JOIN $nodeTable b ON t".($currentDepth ? : '').".ID=b.PARENT_ID ";
			$finalQuery = $finalQuery.$lastJoin." WHERE t.STRUCTURE_ID = $structureId";

			$finalQuery = $helper->getInsertIgnore(
				$nodePathTable,
				'(CHILD_ID, PARENT_ID, DEPTH) ',
				$finalQuery,
			);

			$connection->queryExecute($finalQuery);
			$emptyInsert = $connection->getAffectedRowsCount() <= 0;

			$currentDepth++;
		}
	}
}
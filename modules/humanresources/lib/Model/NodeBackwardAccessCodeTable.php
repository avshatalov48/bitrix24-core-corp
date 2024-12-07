<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlHelper;
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
 * @method static EO_NodeBackwardAccessCode_Query query()
 * @method static EO_NodeBackwardAccessCode_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeBackwardAccessCode_Result getById($id)
 * @method static EO_NodeBackwardAccessCode_Result getList(array $parameters = [])
 * @method static EO_NodeBackwardAccessCode_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCode createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCode wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeBackwardAccessCodeCollection wakeUpCollection($rows)
 */
class NodeBackwardAccessCodeTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodeBackwardAccessCode::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeBackwardAccessCodeCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_backward_access_code';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('NODE_ID'))
				->configureTitle('Node id')
				->configureUnique()
			,
			(new ORM\Fields\StringField('ACCESS_CODE'))
				->configureTitle('Role id')
			,
			(new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'NODE',
				NodeTable::class,
				Join::on('this.NODE_ID', 'ref.ID')
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

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function deleteListByStructureId(int $structureId): \Bitrix\Main\DB\Result
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		$helper = $connection->getSqlHelper();
		$tableName = $helper->quote($entity->getDbTableName());
		$nodeTableName = $helper->quote(NodeTable::getTableName());

		if ($connection->getType() === 'mysql')
		{
			$sql = "
				DELETE $tableName FROM $tableName 
				INNER JOIN $nodeTableName ON $tableName.NODE_ID = $nodeTableName.ID 
				WHERE $nodeTableName.STRUCTURE_ID = $structureId
			";

			return $connection->query($sql);
		}

		$sql = "
			DELETE FROM $tableName WHERE NODE_ID IN (
			SELECT ID FROM $nodeTableName WHERE STRUCTURE_ID = $structureId);
		";

		return $connection->query($sql);
	}
}
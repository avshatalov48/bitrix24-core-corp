<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeMember_Query query()
 * @method static EO_NodeMember_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeMember_Result getById($id)
 * @method static EO_NodeMember_Result getList(array $parameters = [])
 * @method static EO_NodeMember_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeMember createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeMemberCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeMember wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeMemberCollection wakeUpCollection($rows)
 */
class NodeMemberTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodeMember::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeMemberCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_member';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\EnumField('ENTITY_TYPE'))
				->configureValues(MemberEntityType::values())
				->configureDefaultValue(MemberEntityType::USER)
				->configureTitle('Type')
			,
			(new ORM\Fields\IntegerField('ENTITY_ID'))
				->configureTitle('Entity id')
			,
			(new ORM\Fields\BooleanField('ACTIVE'))
				->configureTitle('Active')
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
			,
			(new ORM\Fields\IntegerField('NODE_ID'))
				->configureTitle('Node id')
			,
			(new ORM\Fields\IntegerField('ADDED_BY'))
				->configureTitle('Added by user')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Row created at')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Row updated at')
			,
			(new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'NODE',
				NodeTable::class,
				Join::on('this.NODE_ID', 'ref.ID')
			)),
			(new \Bitrix\Main\ORM\Fields\Relations\ManyToMany(
				'ROLE',
				RoleTable::class,
			))
				->configureMediatorTableName(NodeMemberRoleTable::getTableName())
				->configureLocalPrimary('ID', 'MEMBER_ID')
				->configureLocalReference('MEMBER')
				->configureRemoteReference('ROLE')
				->configureJoinType(Join::TYPE_LEFT)
			,
		];
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteList(array $filter): \Bitrix\Main\DB\Result
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		return $connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}

	public static function deleteListByStructureId(int $structureId): \Bitrix\Main\DB\Result
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$tableName = $connection->getSqlHelper()
			->quote($entity->getDbTableName())
		;
		$nodeTableName = $connection->getSqlHelper()
			->quote(NodeTable::getTableName())
		;

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
<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeMemberRole_Query query()
 * @method static EO_NodeMemberRole_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeMemberRole_Result getById($id)
 * @method static EO_NodeMemberRole_Result getList(array $parameters = [])
 * @method static EO_NodeMemberRole_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeMemberRole createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeMemberRoleCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeMemberRole wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeMemberRoleCollection wakeUpCollection($rows)
 */
class NodeMemberRoleTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodeMemberRole::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeMemberRoleCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_member_role';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('MEMBER_ID'))
				->configureTitle('Member id')
			,
			(new ORM\Fields\IntegerField('ROLE_ID'))
				->configureTitle('Role id')
			,
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureTitle('CREATED_BY')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Member role creation date')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Member role updated at')
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
			Query::buildFilterSql($entity, $filter),
		));
	}

	public static function deleteByNodeId(int $nodeId)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		$nodeMemberTableName = $connection->getSqlHelper()
			->quote(NodeMemberTable::getTableName())
		;
		$nodeMemberRoleTableName = $connection->getSqlHelper()
			->quote(static::getTableName())
		;

		if ($connection->getType() === 'mysql')
		{
			$connection->query(sprintf(
				"DELETE r FROM %s AS r INNER JOIN %s AS m ON r.%s = m.%s WHERE m.%s = %d",
				$nodeMemberRoleTableName,
				$nodeMemberTableName,
				$connection->getSqlHelper()->quote('MEMBER_ID'),
				$connection->getSqlHelper()->quote('ID'),
				$connection->getSqlHelper()->quote('NODE_ID'),
				$nodeId
			));

			return;
		}

		$connection->query(sprintf(
			"DELETE FROM %s WHERE %s IN (SELECT %s FROM %s WHERE %s = %d)",
			$nodeMemberRoleTableName,
			$connection->getSqlHelper()->quote('MEMBER_ID'),
			$connection->getSqlHelper()->quote('ID'),
			$nodeMemberTableName,
			$connection->getSqlHelper()->quote('NODE_ID'),
			$nodeId
		));
	}
}
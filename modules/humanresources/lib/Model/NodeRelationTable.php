<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeRelation_Query query()
 * @method static EO_NodeRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeRelation_Result getById($id)
 * @method static EO_NodeRelation_Result getList(array $parameters = [])
 * @method static EO_NodeRelation_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeRelationCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeRelation wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeRelationCollection wakeUpCollection($rows)
 */
class NodeRelationTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodeRelation::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeRelationCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_relation';
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
			,
			(new ORM\Fields\IntegerField('ENTITY_ID'))
				->configureTitle('Related entity id')
			,
			(new ORM\Fields\EnumField('ENTITY_TYPE'))
				->configureValues(RelationEntityType::names())
				->configureTitle('Entity type chat/space/')
			,
			(new ORM\Fields\EnumField('ENTITY_SUBTYPE'))
				->configureValues(RelationEntitySubtype::values())
				->configureTitle('Entity subtype chat/channel/')
			,
			(new ORM\Fields\BooleanField('WITH_CHILD_NODES'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
			,
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureTitle('CREATED_BY')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('CREATED_AT')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('UPDATED_AT')
			,
		];
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
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
}
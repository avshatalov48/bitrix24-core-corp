<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeRole_Query query()
 * @method static EO_NodeRole_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeRole_Result getById($id)
 * @method static EO_NodeRole_Result getList(array $parameters = [])
 * @method static EO_NodeRole_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeRole createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeRoleCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeRole wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeRoleCollection wakeUpCollection($rows)
 */
class NodeRoleTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return NodeRole::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeRoleCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_role';
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
				->configureTitle('Member id')
			,
			(new ORM\Fields\IntegerField('ROLE_ID'))
				->configureTitle('Role id')
			,
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureTitle('Created by user')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Created at time')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Updated at time')
			,
		];
	}
}
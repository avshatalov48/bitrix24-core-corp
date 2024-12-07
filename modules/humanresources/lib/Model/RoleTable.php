<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\RoleChildAffectionType;
use Bitrix\HumanResources\Type\RoleEntityType;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = [])
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Role createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\RoleCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\Role wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\RoleCollection wakeUpCollection($rows)
 */
class RoleTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return Role::class;
	}

	public static function getCollectionClass(): string
	{
		return RoleCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_role';
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
				->configureValues(RoleEntityType::values())
				->configureTitle('Entity type')
			,
			(new ORM\Fields\StringField('NAME'))
				->configureTitle('Role name')
			,
			(new ORM\Fields\IntegerField('PRIORITY'))
				->configureTitle('Priority')
				->addValidator(
					new ORM\Fields\Validators\RangeValidator()
				)
			,
			(new ORM\Fields\IntegerField('CHILD_AFFECTION_TYPE'))
				->configureTitle('Child affection type')
				->configureDefaultValue(RoleChildAffectionType::NO_AFFECTION->value)
			,
			(new ORM\Fields\StringField('XML_ID'))
				->configureTitle('Role name')
			,
		];
	}
}
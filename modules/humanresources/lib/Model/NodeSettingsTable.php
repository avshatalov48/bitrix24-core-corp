<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\NodeSettingsType;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class NodeSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeSettings_Query query()
 * @method static EO_NodeSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeSettings_Result getById($id)
 * @method static EO_NodeSettings_Result getList(array $parameters = [])
 * @method static EO_NodeSettings_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\NodeSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeSettingsCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\NodeSettings wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeSettingsCollection wakeUpCollection($rows)
 */
class NodeSettingsTable extends ORM\Data\DataManager
{
	use ORM\Data\Internal\DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return NodeSettings::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeSettingsCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node_settings';
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
			(new ORM\Fields\EnumField('SETTINGS_TYPE'))
				->configureValues(NodeSettingsType::values())
				->configureTitle('Settings type')
			,
			(new ORM\Fields\StringField('SETTINGS_VALUE'))
				->configureTitle('Value')
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
}

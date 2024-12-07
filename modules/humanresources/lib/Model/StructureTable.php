<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Type\StructureType;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Structure_Query query()
 * @method static EO_Structure_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Structure_Result getById($id)
 * @method static EO_Structure_Result getList(array $parameters = [])
 * @method static EO_Structure_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Structure createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\EO_Structure_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\Structure wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\EO_Structure_Collection wakeUpCollection($rows)
 */
class StructureTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return Structure::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\StringField('NAME'))
				->configureTitle('Structure name')
			,
			(new ORM\Fields\EnumField('TYPE'))
				->configureValues(StructureType::values())
				->configureDefaultValue(StructureType::DEFAULT)
				->configureTitle('Structure type')
			,
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureTitle('Created by user id')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Created at time')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Updated at time')
			,
			(new ORM\Fields\StringField('XML_ID'))
				->configureTitle('XML_ID')
			,
		];
	}

	public static function onAfterDelete(ORM\Event $event)
	{
		$data = $event->getParameters();
		$structureId = $data["primary"]["ID"];

		NodePathTable::deleteListByStructureId($structureId);
		NodeBackwardAccessCodeTable::deleteListByStructureId($structureId);
		NodeMemberTable::deleteListByStructureId($structureId);
		NodeTable::deleteList(['=STRUCTURE_ID' => $structureId]);
	}
}
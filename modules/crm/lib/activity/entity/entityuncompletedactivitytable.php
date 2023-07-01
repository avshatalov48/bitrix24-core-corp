<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class EntityUncompletedActivityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityUncompletedActivity_Query query()
 * @method static EO_EntityUncompletedActivity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityUncompletedActivity_Result getById($id)
 * @method static EO_EntityUncompletedActivity_Result getList(array $parameters = [])
 * @method static EO_EntityUncompletedActivity_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\Entity\EO_EntityUncompletedActivity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\Entity\EO_EntityUncompletedActivity_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\Entity\EO_EntityUncompletedActivity wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\Entity\EO_EntityUncompletedActivity_Collection wakeUpCollection($rows)
 */
class EntityUncompletedActivityTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_entity_uncompleted_act';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired(),
			(new IntegerField('RESPONSIBLE_ID'))
				->configureRequired(),
			(new DatetimeField('MIN_DEADLINE'))
				->configureRequired(),
			(new BooleanField('IS_INCOMING_CHANNEL'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
			(new BooleanField('HAS_ANY_INCOMING_CHANEL'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
			(new DatetimeField('MIN_LIGHT_COUNTER_AT'))
				->configureRequired(),
		];
	}

	public static function deleteByItemIdentifier(ItemIdentifier $itemIdentifier): void
	{
		$sql = 'DELETE FROM b_crm_entity_uncompleted_act' .
			' WHERE ENTITY_TYPE_ID=' . $itemIdentifier->getEntityTypeId() .
			' AND ENTITY_ID=' . $itemIdentifier->getEntityId()
		;
		Application::getConnection()->query($sql);
	}
}

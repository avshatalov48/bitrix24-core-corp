<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

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

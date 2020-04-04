<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class RecyclebinTable extends Entity\DataManager
{

	public static function getTableName()
	{
		return 'b_recyclebin';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			new Entity\IntegerField(
				'ID', array(
						'primary'      => true,
						'autocomplete' => true
					)
			),
			new Entity\StringField('NAME'),
			new Entity\StringField('SITE_ID'),
			new Entity\StringField('MODULE_ID'),
			new Entity\StringField('ENTITY_ID'),
			new Entity\StringField('ENTITY_TYPE'),
			new Entity\DatetimeField('TIMESTAMP'),
			new Entity\IntegerField('USER_ID'),

			new Entity\ReferenceField(
				'USER', '\Bitrix\Main\User', array('=this.USER_ID' => 'ref.ID')
			),
		);

		return $fieldsMap;
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$result->modifyFields(
			array(
				'TIMESTAMP' => DateTime::createFromTimestamp(time())
			)
		);

		return $result;
	}
}
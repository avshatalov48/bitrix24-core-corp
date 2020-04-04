<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;

class CallCrmEntityTable extends Base
{
	public static function getTableName()
	{
		return 'b_voximplant_call_crm_entity';
	}

	public static function getMap()
	{
		return [
			new StringField('CALL_ID', [
				'primary' => true
			]),
			new StringField('ENTITY_TYPE', [
				'primary' => true
			]),
			new IntegerField('ENTITY_ID', [
				'primary' => true
			]),
			new BooleanField('IS_PRIMARY', [
				'values' => ['N', 'Y']
			]),
			new BooleanField('IS_CREATED', [
				'values' => ['N', 'Y']
			])
		];
	}

	protected static function getMergeFields()
	{
		return ['CALL_ID', 'ENTITY_TYPE', 'ENTITY_ID'];
	}
}
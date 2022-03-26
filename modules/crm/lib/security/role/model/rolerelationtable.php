<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class RoleRelationTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role_relation';
	}

	public static function getMap()
	{
		return [
			'ID' => (new IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			'ROLE_ID' => (new IntegerField('ROLE_ID'))
				->configureRequired(true)
			,
			'RELATION' => (new StringField('RELATION'))
				->configureRequired(true)
			,
		];
	}
}

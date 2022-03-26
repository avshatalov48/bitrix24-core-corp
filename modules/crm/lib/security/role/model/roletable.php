<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;


class RoleTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID', ['primary' => true, 'autocomplete' => true])),
			(new StringField('NAME', ['required' => true, 'size' => 255])),
			(new Reference('PERMISSION', RolePermissionTable::class, Join::on('this.ROLE_ID', 'ref.ID'))),
		];
	}
}

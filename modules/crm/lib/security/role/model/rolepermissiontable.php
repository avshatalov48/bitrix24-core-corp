<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class RolePermissionTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role_perms';
	}

	public static function getMap()
	{
		return [
			(new IntegerField("ID", ["primary" => true, "autocomplete" => true])),
			(new IntegerField("ROLE_ID", ["required" => true])),
			(new StringField("ENTITY", ["required" => true, "size" => 20])),
			(new StringField("FIELD", ["size" => 30, "default" => "-"])),
			(new StringField("FIELD_VALUE", ["size" => 255])),
			(new StringField("PERM_TYPE", ["required" => true, "size" => 20])),
			(new StringField("ATTR", ["size" => 1, "default" => ""])),
		];
	}
}

<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;

Loc::loadMessages(__FILE__);

class RoleTable extends Main\ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role';
	}

	public static function getUFId()
	{
		return 'CRM_LEAD';
	}

	public static function getMap()
	{
		return [
			(new IntegerField("ID", ["primary" => true, "autocomplete" => true])),
			(new StringField("NAME", ["required" => true, "size" => 255])),
			(new Reference("PERMISSION", RolePermissionTable::class, Join::on("this.ROLE_ID", "ref.ID")))
		];
	}
}
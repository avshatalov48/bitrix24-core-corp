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

/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = array())
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Role createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Role_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Role wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Role_Collection wakeUpCollection($rows)
 */
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
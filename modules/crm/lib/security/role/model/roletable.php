<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;


/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = [])
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role wakeUpObject($row)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role_Collection wakeUpCollection($rows)
 */
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
			(new StringField('IS_SYSTEM', ['required' => false, 'size' => 1])),
			(new StringField('CODE', ['required' => false, 'size' => 64])),
			(new Reference('PERMISSION', RolePermissionTable::class, Join::on('this.ROLE_ID', 'ref.ID'))),
		];
	}
}

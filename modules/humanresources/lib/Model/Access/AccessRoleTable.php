<?php

namespace Bitrix\HumanResources\Model\Access;

use Bitrix\Main;

/**
 * Class AccessRoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AccessRole_Query query()
 * @method static EO_AccessRole_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AccessRole_Result getById($id)
 * @method static EO_AccessRole_Result getList(array $parameters = [])
 * @method static EO_AccessRole_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRole_Collection wakeUpCollection($rows)
 */
class AccessRoleTable extends Main\Access\Role\AccessRoleTable
{
	public static function getTableName(): string
	{
		return 'b_hr_access_role';
	}
}
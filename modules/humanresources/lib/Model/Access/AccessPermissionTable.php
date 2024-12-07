<?php

namespace Bitrix\HumanResources\Model\Access;

use Bitrix\Main;

/**
 * Class AccessPermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AccessPermission_Query query()
 * @method static EO_AccessPermission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AccessPermission_Result getById($id)
 * @method static EO_AccessPermission_Result getList(array $parameters = [])
 * @method static EO_AccessPermission_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessPermission_Collection wakeUpCollection($rows)
 */
class AccessPermissionTable extends Main\Access\Permission\AccessPermissionTable
{
	public static function getTableName(): string
	{
		return 'b_hr_access_permission';
	}
}
<?php

namespace Bitrix\BIConnector\Access\Permission;

use Bitrix\Main\Access\Permission\AccessPermissionTable;

/**
 * Class PermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Permission_Query query()
 * @method static EO_Permission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Permission_Result getById($id)
 * @method static EO_Permission_Result getList(array $parameters = [])
 * @method static EO_Permission_Entity getEntity()
 * @method static \Bitrix\BIConnector\Access\Permission\Permission createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection createCollection()
 * @method static \Bitrix\BIConnector\Access\Permission\Permission wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Access\Permission\EO_Permission_Collection wakeUpCollection($rows)
 */
final class PermissionTable extends AccessPermissionTable
{
	public static function getTableName()
	{
		return 'b_biconnector_permission';
	}

	public static function getObjectClass()
	{
		return Permission::class;
	}
}

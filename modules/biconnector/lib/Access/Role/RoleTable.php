<?php

namespace Bitrix\BIConnector\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleTable;

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
 * @method static \Bitrix\BIConnector\Access\Role\Role createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Access\Role\RoleCollection createCollection()
 * @method static \Bitrix\BIConnector\Access\Role\Role wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Access\Role\RoleCollection wakeUpCollection($rows)
 */
final class RoleTable extends AccessRoleTable
{
	public static function getTableName(): string
	{
		return 'b_biconnector_role';
	}

	public static function getObjectClass(): string
	{
		return Role::class;
	}

	public static function getCollectionClass(): string
	{
		return RoleCollection::class;
	}
}

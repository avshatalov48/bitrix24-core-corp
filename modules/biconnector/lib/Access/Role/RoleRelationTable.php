<?php

namespace Bitrix\BIConnector\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

/**
 * Class RoleRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleRelation_Query query()
 * @method static EO_RoleRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleRelation_Result getById($id)
 * @method static EO_RoleRelation_Result getList(array $parameters = [])
 * @method static EO_RoleRelation_Entity getEntity()
 * @method static \Bitrix\BIConnector\Access\Role\RoleRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection createCollection()
 * @method static \Bitrix\BIConnector\Access\Role\RoleRelation wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Access\Role\EO_RoleRelation_Collection wakeUpCollection($rows)
 */
final class RoleRelationTable extends AccessRoleRelationTable
{
	public static function getTableName()
	{
		return 'b_biconnector_role_relation';
	}

	public static function getObjectClass()
	{
		return RoleRelation::class;
	}
}

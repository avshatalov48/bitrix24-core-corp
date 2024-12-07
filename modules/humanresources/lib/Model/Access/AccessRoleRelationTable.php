<?php

namespace Bitrix\HumanResources\Model\Access;

use Bitrix\Main;

/**
 * Class HumanResourcesRoleRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AccessRoleRelation_Query query()
 * @method static EO_AccessRoleRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AccessRoleRelation_Result getById($id)
 * @method static EO_AccessRoleRelation_Result getList(array $parameters = [])
 * @method static EO_AccessRoleRelation_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\Access\EO_AccessRoleRelation_Collection wakeUpCollection($rows)
 */
class AccessRoleRelationTable extends Main\Access\Role\AccessRoleRelationTable
{
	public static function getTableName(): string
	{
		return 'b_hr_access_role_relation';
	}
}
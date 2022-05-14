<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

/**
 * Class TasksRoleRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TasksRoleRelation_Query query()
 * @method static EO_TasksRoleRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TasksRoleRelation_Result getById($id)
 * @method static EO_TasksRoleRelation_Result getList(array $parameters = [])
 * @method static EO_TasksRoleRelation_Entity getEntity()
 * @method static \Bitrix\Tasks\Access\Role\TasksRoleRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection createCollection()
 * @method static \Bitrix\Tasks\Access\Role\TasksRoleRelation wakeUpObject($row)
 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection wakeUpCollection($rows)
 */
class TasksRoleRelationTable extends AccessRoleRelationTable
{
	public static function getTableName()
	{
		return 'b_tasks_role_relation';
	}

	public static function getObjectClass()
	{
		return TasksRoleRelation::class;
	}
}
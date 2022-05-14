<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Permission;

use Bitrix\Main\Access\Permission\AccessPermissionTable;

/**
 * Class TasksPermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TasksPermission_Query query()
 * @method static EO_TasksPermission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TasksPermission_Result getById($id)
 * @method static EO_TasksPermission_Result getList(array $parameters = [])
 * @method static EO_TasksPermission_Entity getEntity()
 * @method static \Bitrix\Tasks\Access\Permission\TasksPermission createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection createCollection()
 * @method static \Bitrix\Tasks\Access\Permission\TasksPermission wakeUpObject($row)
 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection wakeUpCollection($rows)
 */
class TasksPermissionTable extends AccessPermissionTable
{
	public static function getTableName()
	{
		return 'b_tasks_permission';
	}

	public static function getObjectClass()
	{
		return TasksPermission::class;
	}
}
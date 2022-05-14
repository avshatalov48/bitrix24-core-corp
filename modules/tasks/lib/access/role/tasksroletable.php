<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
namespace Bitrix\Tasks\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleTable;

/**
 * Class TasksRoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TasksRole_Query query()
 * @method static EO_TasksRole_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TasksRole_Result getById($id)
 * @method static EO_TasksRole_Result getList(array $parameters = [])
 * @method static EO_TasksRole_Entity getEntity()
 * @method static \Bitrix\Tasks\Access\Role\TasksRole createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection createCollection()
 * @method static \Bitrix\Tasks\Access\Role\TasksRole wakeUpObject($row)
 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection wakeUpCollection($rows)
 */
class TasksRoleTable extends AccessRoleTable
{

	public static function getTableName()
	{
		return 'b_tasks_role';
	}

	public static function getObjectClass()
	{
		return TasksRole::class;
	}
}
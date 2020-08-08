<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
namespace Bitrix\Tasks\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleTable;

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
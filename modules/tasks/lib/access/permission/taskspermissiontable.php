<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Permission;

use Bitrix\Main\Access\Permission\AccessPermissionTable;

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
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

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
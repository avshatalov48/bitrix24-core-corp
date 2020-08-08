<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Role;

use Bitrix\Tasks\Access\Permission\TasksPermissionTable;

class RoleUtil extends \Bitrix\Main\Access\Role\RoleUtil
{

	protected static function getRoleTableClass(): string
	{
		return TasksRoleTable::class;
	}

	protected static function getRoleRelationTableClass(): string
	{
		return TasksRoleRelationTable::class;
	}

	protected static function getPermissionTableClass(): string
	{
		return TasksPermissionTable::class;
	}

	protected static function getRoleDictionaryClass(): ?string
	{
		return RoleDictionary::class;
	}

}
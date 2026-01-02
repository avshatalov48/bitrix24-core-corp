<?php

namespace Bitrix\HumanResources\Install\Agent\Access;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\HumanResources\Access\Role;

final class SetInviteAndFirePermissionsAgent
{
	private const DEFAULT_ROLES = [
		RoleDictionary::ROLE_ADMIN => [
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE => PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => 1,
		],
		RoleDictionary::ROLE_HR=> [
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_ALL,
		],
		RoleDictionary::ROLE_DIRECTOR=> [
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
		],
		RoleDictionary::ROLE_DEPUTY=> [
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
		],
		RoleDictionary::ROLE_EMPLOYEE=> [
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_NONE,
		],
	];

	public static function run(): string
	{
		foreach (self::DEFAULT_ROLES as $roleName => $permissions)
		{
			$role = self::getRoleByName($roleName);

			if (!$role)
			{
				continue;
			}

			try
			{
				$currentRolePermissions = $role->getPermissions();
				$updatedRolePermissions =  $permissions + $currentRolePermissions;
				$role->updatePermissions($updatedRolePermissions);
			}
			catch (\Exception $e)
			{}
		}

		return '';
	}

	private static function getRoleByName(string $roleName): ?Role\RoleUtil
	{
		$role = AccessRoleTable::query()
			->setSelect(['ID'])
			->where('NAME', $roleName)
			->setLimit(1)
			->exec()
			->fetch()
		;

		if ($role && $role['ID'])
		{
			return new Role\RoleUtil((int)$role['ID']);
		}

		return null;
	}
}
<?php

namespace Bitrix\Crm\Agent\Security\Service\Support;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Utils\RolePermissionChecker;

final class PermissionsUtil
{
	public static function findPermAllowingCrmConfig(EO_Role $role): ?EO_RolePermission
	{
		foreach ($role->getPermissions()?->getAll() ?? [] as $permission)
		{
			if (self::isAllowsCrmConfig($permission))
			{
				return $permission;
			}
		}

		return null;
	}

	public static function isRoleAllowedCrmConfig(EO_Role $role): bool
	{
		return self::findPermAllowingCrmConfig($role) !== null;
	}

	public static function isAllowsCrmConfig(EO_RolePermission $permission): bool
	{
		$model = PermissionModel::createFromEntityObject($permission);

		return
			$model->entity() === 'CONFIG'
			&& $model->permissionCode() === 'WRITE'
			&& RolePermissionChecker::isPermissionAllowsAnything($model)
		;
	}
}

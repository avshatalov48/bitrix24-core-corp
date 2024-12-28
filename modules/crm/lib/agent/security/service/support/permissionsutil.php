<?php

namespace Bitrix\Crm\Agent\Security\Service\Support;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Utils\RolePermissionChecker;

final class PermissionsUtil
{
	public static function findNotEmptyCrmConfig(EO_Role $role): ?EO_RolePermission
	{
		foreach ($role->getPermissions()?->getAll() ?? [] as $permission)
		{
			if (self::isNotEmptyCrmConfig($permission))
			{
				return $permission;
			}
		}

		return null;
	}

	public static function hasNotEmptyCrmConfig(EO_Role $role): bool
	{
		return self::findNotEmptyCrmConfig($role) !== null;
	}

	public static function isNotEmptyCrmConfig(EO_RolePermission $permission): bool
	{
		$model = PermissionModel::createFromEntityObject($permission);

		return
			$model->entity() === 'CONFIG'
			&& $model->permissionCode() === 'WRITE'
			&& !RolePermissionChecker::isPermissionEmpty($model)
		;
	}
}

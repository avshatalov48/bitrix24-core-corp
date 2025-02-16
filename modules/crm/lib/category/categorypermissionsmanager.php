<?php
namespace Bitrix\Crm\Category;

use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Main\Result;
use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;

class CategoryPermissionsManager
{
	use Singleton;

	public function setPermissions(CategoryIdentifier $categoryIdentifier, string $permissionLevel): Result
	{
		$permissions = match($permissionLevel)
		{
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL => RolePreset::getDefaultPermissionSetForEntity($categoryIdentifier),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE => RolePreset::getMinPermissionSetForEntity($categoryIdentifier),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_SELF => $this->getSelfPermissionSetForEntity($categoryIdentifier),
			default => [],
		};
		$needStrictByRoleGroupCode = ($permissionLevel != \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE);

		return RolePermission::setByEntityIdForAllNotAdminRoles($categoryIdentifier, $permissions, $needStrictByRoleGroupCode);
	}

	public function copyPermissions(CategoryIdentifier $fromCategory, CategoryIdentifier $toCategory): Result
	{
		if ($fromCategory->getEntityTypeId() !== $toCategory->getEntityTypeId())
		{
			throw new ArgumentException('Source and destination entityTypeIds must be the same');
		}
		if (is_null($fromCategory->getCategoryId()))
		{
			throw new ArgumentException('Source category Id must be defined');
		}
		if (is_null($toCategory->getCategoryId()))
		{
			throw new ArgumentException('Destination category Id must be defined');
		}

		\CCrmRole::EraseEntityPermissionsForNotAdminRoles($toCategory->getPermissionEntityCode()); // clear all not admin roles permissions before copy

		$permissionSet = RolePermission::getByEntityId($fromCategory->getPermissionEntityCode());

		return RolePermission::setByEntityId($toCategory->getPermissionEntityCode(), $permissionSet, true);
	}

	private function getSelfPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		$permissions = [];
		$userPermissions = Container::getInstance()->getUserPermissions();

		$permissionEntityCode = $categoryIdentifier->getPermissionEntityCode();

		$permissionEntities = \Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder::getInstance()->buildModels();
		foreach ($permissionEntities as $permissionEntity)
		{
			if ($permissionEntityCode === $permissionEntity->code())
			{
				foreach ($permissionEntity->permissions() as $permission)
				{
					$defaultAttr = (
						$permission->variants()?->has($userPermissions::PERMISSION_SELF) // permission supports 'A' value?
						|| $permission->variants()?->has(UserRoleAndHierarchy::SELF) // permission supports 'SELF' value?
					)
						? $userPermissions::PERMISSION_SELF
						: $permission->getDefaultAttribute()
					;
					$defaultSettings = $permission->getDefaultSettings();

					$permissionCode = $permission->code();
					if (!is_null($defaultAttr) || !empty($defaultSettings))
					{
						if (!isset($permissionSet[$permissionCode]))
						{
							$permissionSet[$permissionCode] = [
								'-' => []
							];
						}
						$permissions[$permissionCode]['-']['ATTR'] = $defaultAttr;
						$permissions[$permissionCode]['-']['SETTINGS'] = empty($defaultSettings) ? null : $defaultSettings;
					}
				}
				break;
			}
		}

		return $permissions;
	}
}

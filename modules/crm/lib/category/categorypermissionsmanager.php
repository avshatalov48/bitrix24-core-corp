<?php
namespace Bitrix\Crm\Category;

use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Result;
use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;

class CategoryPermissionsManager
{
	use Singleton;

	public function setPermissions(CategoryIdentifier $categoryIdentifier, string $permissionLevel): Result
	{
		$permissions = match($permissionLevel)
		{
			UserPermissions::PERMISSION_ALL => RolePreset::getMaxPermissionSetForEntity($categoryIdentifier),
			UserPermissions::PERMISSION_NONE => RolePreset::getMinPermissionSetForEntity($categoryIdentifier),
			UserPermissions::PERMISSION_SELF => RolePreset::getSelfPermissionSetForEntity($categoryIdentifier),
			default => [],
		};
		$needStrictByRoleGroupCode = ($permissionLevel !== \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE);

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
}

<?php

namespace Bitrix\HumanResources\Access\Install;

use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Item;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class AccessInstaller
{
	public static function installAgent(): string
	{
		self::fillDefaultSystemPermissions();
		return '';
	}

	public static function reInstallAgent(): string
	{
		self::reInstall();
		return '';
	}

	private static function reInstall(): void
	{
		$roleRepository = new RoleRepository();
		if (!$roleRepository->areRolesDefined())
		{
			self::fillDefaultSystemPermissions();

			return;
		}

		$roles = $roleRepository->getRoleList();

		foreach ($roles as $role)
		{
			$roleUtil = new Role\RoleUtil($role['ID']);

			$roleUtil->deleteRole();
		}

		self::fillDefaultSystemPermissions();
	}

	private static function fillDefaultSystemPermissions(): void
	{
		$roleRepository = new RoleRepository();
		if ($roleRepository->areRolesDefined())
		{
			return;
		}

		$defaultMap = Role\RoleUtil::getDefaultMap();

		$permissionCollection = new PermissionCollection();
		$permissionRepository = new PermissionRepository();

		foreach ($defaultMap as $roleName => $rolePermissions)
		{
			$role = $roleRepository->create($roleName);
			if (!$role->isSuccess())
			{
				continue;
			}

			self::installRelation($roleName, $role);

			$roleId = $role->getId();
			foreach ($rolePermissions as $permission)
			{
				$permissionCollection->add(
					new Item\Access\Permission(
						roleId: (int)$roleId,
						permissionId: $permission['id'],
						value: (int)$permission['value'],
					),
				);
			}
		}

		if (!$permissionCollection->empty())
		{
			$permissionRepository->createByCollection($permissionCollection);
		}
	}

	private static function getRelation(int|string $roleName): ?string
	{
		return match ($roleName) {
			Role\RoleDictionary::ROLE_DIRECTOR => AccessCode::ACCESS_DIRECTOR . '0',
			Role\RoleDictionary::ROLE_EMPLOYEE => AccessCode::ACCESS_EMPLOYEE . '0',
			default => null,
		};
	}

	/**
	 * @param int|string $roleName
	 * @param \Bitrix\Main\ORM\Data\AddResult $role
	 *
	 * @return void
	 * @throws RoleRelationSaveException
	 */
	public static function installRelation(
		int|string $roleName,
		\Bitrix\Main\ORM\Data\AddResult $role,
	): void
	{
		if (self::getRelation($roleName))
		{
			$roleUtil = new Role\RoleUtil($role->getId());
			$roleUtil->updateRoleRelations(array_flip([self::getRelation($roleName)]));
		}
	}
}
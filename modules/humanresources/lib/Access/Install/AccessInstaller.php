<?php

namespace Bitrix\HumanResources\Access\Install;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\Rule\UserInviteRule;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Repository\Access\RoleRelationRepository;
use Bitrix\HumanResources\Item;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

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

	public static function reInstallInviteRuleAgent():string
	{
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
			try
			{
				$permissionRepository->createByCollection($permissionCollection);
			}
			catch (\Exception $e)
			{
			}
		}
	}

	private static function getRelation(int|string $roleName): ?string
	{
		return match ($roleName) {
			Role\RoleDictionary::ROLE_DIRECTOR => AccessCode::ACCESS_DIRECTOR . '0',
			Role\RoleDictionary::ROLE_EMPLOYEE => AccessCode::ACCESS_EMPLOYEE . '0',
			Role\RoleDictionary::ROLE_ADMIN => 'G1',
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

	private static function reInstallInviteRule(): void
	{
		$permissionRepository = new PermissionRepository();
		$roleRepository = new RoleRepository();
		if (
			!Loader::includeModule('bitrix24')
			|| Option::get('bitrix24', 'allow_invite_users', 'N') !== 'Y'
		)
		{
			$role = $roleRepository->getRoleObjectByName(RoleDictionary::ROLE_ADMIN);
			if (!$role)
			{
				return;
			}

			$permissionRepository->setPermissionByRoleId(
				$role->getId(),
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE,
				UserInviteRule::VARIABLE_AVAILABLE,
			);

			return;
		}

		$roles = $roleRepository->getRoleList();
		foreach ($roles as $role)
		{
			$permissionRepository->setPermissionByRoleId(
				(int)$role['ID'],
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE,
				UserInviteRule::VARIABLE_AVAILABLE,
			);
		}
	}

	private static function resetStandartRoleKeys()
	{
		$roleRelationRepository = new RoleRelationRepository();
		$adminAccessCode = 'G1';
		$adminRoleIds = $roleRelationRepository->getRolesByRelationCodes([$adminAccessCode]);
		if (!empty($adminRoleIds))
		{
			$adminRoleUtil = new Role\RoleUtil($adminRoleIds[0]);
			$adminRoleUtil->updateTitle(Role\RoleDictionary::ROLE_ADMIN);
		}

		$directorAccessCode = AccessCode::ACCESS_DIRECTOR . '0';
		$directorRoleIds = $roleRelationRepository->getRolesByRelationCodes([$directorAccessCode]);
		if (!empty($directorRoleIds))
		{
			$directorRoleUtil = new Role\RoleUtil($directorRoleIds[0]);
			$directorRoleUtil->updateTitle(Role\RoleDictionary::ROLE_DIRECTOR);
		}

		$employeeAccessCode = AccessCode::ACCESS_EMPLOYEE . '0';
		$employeeRoleIds = $roleRelationRepository->getRolesByRelationCodes([$employeeAccessCode]);
		if (!empty($employeeRoleIds))
		{
			$employeeRoleUtil = new Role\RoleUtil($employeeRoleIds[0]);
			$employeeRoleUtil->updateTitle(Role\RoleDictionary::ROLE_EMPLOYEE);
		}
	}
}
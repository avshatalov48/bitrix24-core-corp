<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;
use Bitrix\HumanResources\Repository\Access\RoleRelationRepository;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\Db\SqlQueryException;

class InviteAndFirePermissionInstaller extends BaseInstaller
{
	/**
	 * @throws SqlQueryException
	 */
	protected function run(): void
	{
		if (Feature::instance()->isHRInvitePermissionAvailable())
		{
			return;
		}

		$this->resetOldDefaultRoleKeys();
		$this->removeAllAdminsRelations();
		try
		{
			$this->setInviteAndFirePermissions();
		}
		catch (Main\Access\Exception\AccessException)
		{
			Feature::instance()->setHRInvitePermissionAvailable(true);
			Feature::instance()->setHRFirePermissionAvailable(true);
		}
	}

	private function resetOldDefaultRoleKeys(): void
	{
		$defaultRoleKeys = [
			RoleDictionary::ROLE_DIRECTOR=> AccessCode::ACCESS_DIRECTOR . '0',
			RoleDictionary::ROLE_EMPLOYEE=> AccessCode::ACCESS_EMPLOYEE . '0',
		];

		$roleRepository = new RoleRepository();
		$roleRelationRepository = new RoleRelationRepository();
		foreach ($defaultRoleKeys as $roleName => $accessCode)
		{
			$role = $this->getRoleByName($roleName);
			if ($role)
			{
				continue;
			}

			$roleIds = $roleRelationRepository->getRolesByRelationCodes([$accessCode]);
			foreach ($roleIds as $roleId)
			{
				$currentRoleName = $roleRepository->getRoleNameById($roleId);
				if (
					!$currentRoleName
					|| in_array($currentRoleName, array_keys($defaultRoleKeys), true)
					|| $roleRelationRepository->getRoleRelationsCountByRoleId($roleId) > 1
				)
				{
					continue;
				}

				$roleUtil = new Role\RoleUtil($roleId);
				$roleUtil->updateTitle($roleName);

				break;
			}
		}
	}

	private static function removeAllAdminsRelations(): void
	{
		AccessRoleRelationTable::deleteList([
			'=RELATION' => 'G1',
		]);
	}

	/**
	 * @return void
	 * @throws Main\Access\Exception\RoleNotFoundException
	 * @throws Main\Access\Exception\RoleSaveException
	 * @throws Main\Db\SqlQueryException
	 */
	private function setInviteAndFirePermissions(): void
	{
		$defaultRolesPermission = [
			RoleDictionary::ROLE_HR=> [
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_ALL,
				PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => PermissionVariablesDictionary::VARIABLE_NONE,
			],
			RoleDictionary::ROLE_DIRECTOR=> [
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => PermissionVariablesDictionary::VARIABLE_NONE,
			],
			RoleDictionary::ROLE_DEPUTY=> [
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
				PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => PermissionVariablesDictionary::VARIABLE_NONE,
			],
			RoleDictionary::ROLE_EMPLOYEE=> [
				PermissionDictionary::HUMAN_RESOURCES_USER_INVITE=> PermissionVariablesDictionary::VARIABLE_NONE,
				PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE => PermissionVariablesDictionary::VARIABLE_NONE,
			],
		];

		foreach ($defaultRolesPermission as $roleName => $permissions)
		{
			$role = $this->getRoleByName($roleName);
			if (!$role)
			{
				continue;
			}

			$currentRolePermissions = $role->getPermissions();
			$updatedRolePermissions =  $permissions + $currentRolePermissions;
			$role->updatePermissions($updatedRolePermissions);
		}

		Feature::instance()->setHRInvitePermissionAvailable(true);
		Feature::instance()->setHRFirePermissionAvailable(true);
	}

	private function getRoleByName(string $roleName): ?Role\RoleUtil
	{
		$role =  Container::getAccessRoleRepository()
			->getRoleObjectByNameAndCategory(
				$roleName,
				RoleCategory::Department,
			);

		if ($role && $role->getId())
		{
			return new Role\RoleUtil($role->getId());
		}

		return null;
	}
}
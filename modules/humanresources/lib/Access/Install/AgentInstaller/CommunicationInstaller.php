<?php

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Item\Access\Permission;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Model\Access\EO_AccessRole;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Service\Container;

class CommunicationInstaller extends BaseInstaller
{
	protected function run(): void
	{
		$permissionCollection = new PermissionCollection();
		$permissionRepository = Container::getAccessPermissionRepository();

		$targetPermissions = [
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				PermissionValueType::TeamValue,
			),
			TeamPermissionMapper::makeTeamPermissionId(
				PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
				PermissionValueType::DepartmentValue,
			),
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT,
		];

		$departmentTargetRoles = Role\RoleUtil::getDefaultMap();
		$teamTargetRoles = Role\RoleUtil::getDefaultTeamMap();
		$this->processRoles($departmentTargetRoles, RoleCategory::Department, $permissionCollection, $targetPermissions);
		$this->processRoles($teamTargetRoles, RoleCategory::Team, $permissionCollection, $targetPermissions);

		if (!$permissionCollection->empty())
		{
			try
			{
				$permissionRepository->createByCollection($permissionCollection);
			}
			catch (\Exception $e)
			{
				Container::getStructureLogger()->write([
					'entityType' => 'access',
					'message' => $e->getMessage(),
				]);
			}
		}
	}

	private function processRoles(array $targetRoles, RoleCategory $roleCategory, PermissionCollection $permissionCollection, array $targetPermissions): void
	{
		foreach ($targetRoles as $roleName => $permissionArray)
		{
			$role = $this->getRoleByNameAndCategory($roleName, $roleCategory);
			if (!$role)
			{
				continue;
			}

			$permissionArray = array_filter($permissionArray, static fn($permission) => in_array($permission['id'], $targetPermissions, true));
			foreach ($permissionArray as $permission)
			{
				$permissionCollection->add(
					new Permission(
						roleId: $role->getId(),
						permissionId: (string)$permission['id'],
						value: (int)$permission['value'],
					),
				);
			}
		}
	}

	private function getRoleByNameAndCategory(string $roleName, RoleCategory $roleCategory): ?EO_AccessRole
	{
		return Container::getAccessRoleRepository()->getRoleObjectByNameAndCategory($roleName, $roleCategory);
	}
}

<?php

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Item\Access\Permission;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Model\Access\EO_AccessRole;
use Bitrix\HumanResources\Service\Container;

class CommunicationInstallerV2 extends BaseInstaller
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
		];

		$this->processRole(
			RoleDictionary::ROLE_DIRECTOR,
			(new Role\System\Team\DepartmentDirector())->getMap(),
			$permissionCollection,
			$targetPermissions,
		);

		$this->processRole(
			RoleDictionary::ROLE_DEPUTY,
			(new Role\System\Team\DepartmentDeputy())->getMap(),
			$permissionCollection,
			$targetPermissions,
		);

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

	private function processRole(
		string $roleName,
		array $permissionArray,
		PermissionCollection $permissionCollection,
		array $targetPermissions
	): void
	{
		$roleId = (int)$this->getRoleByNameAndCategory($roleName, RoleCategory::Team)?->getId();
		if (!$roleId || $this->checkRoleHasPermissions($roleId, $targetPermissions))
		{
			return;
		}

		$permissionArray = array_filter(
			$permissionArray,
			static fn($permission) => in_array($permission['id'], $targetPermissions, true)
		);

		foreach ($permissionArray as $permission)
		{
			$permissionCollection->add(
				new Permission(
					roleId: $roleId,
					permissionId: (string)$permission['id'],
					value: (int)$permission['value'],
				),
			);
		}
	}

	private function getRoleByNameAndCategory(string $roleName, RoleCategory $roleCategory): ?EO_AccessRole
	{
		return Container::getAccessRoleRepository()->getRoleObjectByNameAndCategory($roleName, $roleCategory);
	}

	private function checkRoleHasPermissions(int $roleId, array $targetPermissions): bool
	{
		$permission = AccessPermissionTable::query()
			->addSelect('ID')
			->where('ROLE_ID', $roleId)
			->whereIn('PERMISSION_ID', $targetPermissions)
			->where('VALUE', '>', 0)
			->setLimit(1)
			->fetch()
		;

		return $permission !== false && !empty($permission['ID']);
	}
}

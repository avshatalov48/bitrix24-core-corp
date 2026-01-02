<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Access\Permission;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Model\Access\EO_AccessRole;
use Bitrix\HumanResources\Model\EO_Role;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class TeamRoleViewAllCompanyInstaller extends BaseInstaller
{
	protected function run(): void
	{
		if (!$this->getTeamRole(RoleDictionary::ROLE_EMPLOYEE))
		{
			return;
		}

		$this->updatePermissions();
	}

	private function getTeamRole(string $roleName): EO_AccessRole
	{
		return Container::getAccessRoleRepository()
				->getRoleObjectByNameAndCategory(
					$roleName,
					RoleCategory::Team,
				)
			;
	}

	private function updatePermissions(): void
	{
		$permissionCollection = new PermissionCollection();
		$permissionRepository = new PermissionRepository();

		$rolesToUpdate = [
			RoleDictionary::ROLE_STRUCTURE_ADMIN,
			RoleDictionary::ROLE_DIRECTOR,
			RoleDictionary::ROLE_DEPUTY,
			RoleDictionary::ROLE_EMPLOYEE,
		];

		$rolePermissions = (new Role\System\Team\DepartmentEmployee())->getMap();

		foreach ($rolesToUpdate as $roleName)
		{
			$roleId = $this->getTeamRole($roleName)?->getId();

			if (!$roleId)
			{
				continue;
			}

			foreach ($rolePermissions as $permission)
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
}
<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class PermissionReInstaller extends BaseInstaller
{
	/**
	 * @throws WrongStructureItemException
	 * @throws RoleRelationSaveException
	 */
	protected function run(): void
	{
		$roleRepository = new RoleRepository();
		if (!$roleRepository->areRolesDefined())
		{
			return;
		}

		$roles = $roleRepository->getRoleList();

		foreach ($roles as $role)
		{
			$roleUtil = new Role\RoleUtil((int)$role['ID']);

			$roleUtil->deleteRole();
		}
	}
}
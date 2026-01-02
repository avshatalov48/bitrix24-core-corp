<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class TeamRolesInstaller extends BaseInstaller
{
	/**
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	protected function run(): void
	{
		if (!$this->isTeamRoleDefined())
		{
			$this->fillDefaultSystemPermissions(
				[
					RoleDictionary::ROLE_STRUCTURE_ADMIN => (new Role\System\Team\Admin())->getMap(),
					RoleDictionary::ROLE_DIRECTOR => (new Role\System\Team\DepartmentDirector())->getMap(),
					RoleDictionary::ROLE_DEPUTY => (new Role\System\Team\DepartmentDeputy())->getMap(),
					RoleDictionary::ROLE_EMPLOYEE => (new Role\System\Team\DepartmentEmployee())->getMap(),
				],
				true,
				RoleCategory::Team
			);
		}
	}

	private function isTeamRoleDefined(): bool
	{
		return Container::getAccessRoleRepository()
			->getRoleObjectByNameAndCategory(
				RoleDictionary::ROLE_STRUCTURE_ADMIN,
				RoleCategory::Team,
			) !== null
		;
	}
}

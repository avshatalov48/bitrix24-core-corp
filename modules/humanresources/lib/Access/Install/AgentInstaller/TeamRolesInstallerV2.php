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

class TeamRolesInstallerV2 extends BaseInstaller
{
	/**
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	protected function run(): void
	{
		if (!$this->isTeamDirectorRoleDefined())
		{
			$this->fillDefaultSystemPermissions(
				[
					RoleDictionary::ROLE_TEAM_DIRECTOR => (new Role\System\Team\Director())->getMap(),
					RoleDictionary::ROLE_TEAM_DEPUTY => (new Role\System\Team\Deputy())->getMap(),
					RoleDictionary::ROLE_TEAM_EMPLOYEE => (new Role\System\Team\Employee())->getMap(),
				],
				true,
				RoleCategory::Team
			);
		}
	}

	private function isTeamDirectorRoleDefined(): bool
	{
		return Container::getAccessRoleRepository()
			->getRoleObjectByNameAndCategory(
				RoleDictionary::ROLE_TEAM_DIRECTOR,
				RoleCategory::Team,
			) !== null
		;
	}

	/**
	 * @param int|string $roleName
	 *
	 * @return string|null
	 */
	protected static function getRelation(int|string $roleName): ?string
	{
		return match ($roleName) {
			Role\RoleDictionary::ROLE_TEAM_DIRECTOR => AccessCode::ACCESS_TEAM_DIRECTOR . '0',
			Role\RoleDictionary::ROLE_TEAM_DEPUTY => AccessCode::ACCESS_TEAM_DEPUTY . '0',
			Role\RoleDictionary::ROLE_TEAM_EMPLOYEE => AccessCode::ACCESS_TEAM_EMPLOYEE . '0',
			default => null,
		};
	}
}

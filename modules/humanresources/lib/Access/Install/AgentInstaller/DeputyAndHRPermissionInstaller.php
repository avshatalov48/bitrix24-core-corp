<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class DeputyAndHRPermissionInstaller extends BaseInstaller
{
	/**
	 * @throws RoleRelationSaveException
	 * @throws WrongStructureItemException
	 */
	protected function run(): void
	{
		if ($this->isDeputyRoleDefined() || $this->isHRRoleDefined())
		{
			return;
		}

		$this->fillDefaultSystemPermissions(
			[
				RoleDictionary::ROLE_DEPUTY => (new Role\System\Deputy())->getMap(),
				RoleDictionary::ROLE_HR => (new Role\System\HR())->getMap(),
			],
			true,
		);
	}

	private static function isDeputyRoleDefined(): bool
	{
		return Container::getAccessRoleRepository()
				->getRoleObjectByNameAndCategory(
					RoleDictionary::ROLE_DEPUTY,
					RoleCategory::Department,
				) !== null
			;
	}
	private static function isHRRoleDefined(): bool
	{
		return Container::getAccessRoleRepository()
				->getRoleObjectByNameAndCategory(
					RoleDictionary::ROLE_HR,
					RoleCategory::Department,
				) !== null
			;
	}
}
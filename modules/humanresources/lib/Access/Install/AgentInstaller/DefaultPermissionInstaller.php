<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class DefaultPermissionInstaller extends BaseInstaller
{
	/**
	 * @throws WrongStructureItemException
	 * @throws RoleRelationSaveException
	 */
	protected function run(): void
	{
		if ($this->isAdminRoleDefined())
		{
			return;
		}

		$this->fillDefaultSystemPermissions(Role\RoleUtil::getDefaultMap());

		Feature::instance()->setHRInvitePermissionAvailable(true);
		Feature::instance()->setHRFirePermissionAvailable(true);
	}

	private function isAdminRoleDefined(): bool
	{
		return Container::getAccessRoleRepository()
				->getRoleObjectByNameAndCategory(
					RoleDictionary::ROLE_STRUCTURE_ADMIN,
					RoleCategory::Department,
				) !== null
			;
	}
}
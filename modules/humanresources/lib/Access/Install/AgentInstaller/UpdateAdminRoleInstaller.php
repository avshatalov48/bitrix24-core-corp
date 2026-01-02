<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Model\Access\EO_AccessRole;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;

class UpdateAdminRoleInstaller extends BaseInstaller
{
	protected function run(): void
	{
		$roleAdmin = $this->getAdmin();
		if (!$roleAdmin)
		{
			return;
		}

		$roleAdmin->setName(RoleDictionary::ROLE_STRUCTURE_ADMIN);
		$roleAdmin->save();
	}

	private function getAdmin(): ?EO_AccessRole
	{
		return Container::getAccessRoleRepository()
				->getRoleObjectByNameAndCategory(
					RoleDictionary::ROLE_ADMIN,
					RoleCategory::Department,
				)
			;
	}
}
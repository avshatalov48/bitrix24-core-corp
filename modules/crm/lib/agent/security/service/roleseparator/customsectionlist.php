<?php

namespace Bitrix\Crm\Agent\Security\Service\RoleSeparator;

use Bitrix\Crm\Agent\Security\Service\RoleSeparator;
use Bitrix\Crm\Agent\Security\Service\Support\PermissionsUtil;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\Permissions\AutomatedSolution\Config;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;

final class CustomSectionList extends RoleSeparator
{
	protected function isPossibleToTransmit(EO_RolePermission $permission): bool
	{
		/**
		 * Create a copy and add new rights to it. We do not affect the existing rights of the original role.
		 * @see CustomSectionList::expandPermissions
		 */

		return false;
	}

	protected function generateGroupCode(): string
	{
		return GroupCodeGenerator::getAutomatedSolutionListCode();
	}

	protected function expandPermissions(EO_Role $copy, EO_Role $originalRole): void
	{
		if (!PermissionsUtil::hasNotEmptyCrmConfig($originalRole))
		{
			return;
		}

		$copy->addToPermissions($this->permission(new Write()));
		$copy->addToPermissions($this->permission(new Config()));
	}

	private function permission(Permission $permission): EO_RolePermission
	{
		return RolePermissionTable
			::createObject()
			->setEntity(AutomatedSolutionList::ENTITY_CODE)
			->setPermType($permission->code())
			->setAttr($permission->getMaxAttributeValue())
			->setSettings(null)
		;
	}
}

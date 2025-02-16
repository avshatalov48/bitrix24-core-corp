<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;
use Bitrix\Main\Localization\Loc;

class SaleTarget implements PermissionEntity
{
	private function permissions(): array
	{
		$hierarchy = (new UserRoleAndHierarchy())
			->exclude(UserRoleAndHierarchy::THIS_ROLE)
			->exclude(UserRoleAndHierarchy::OPEN)
		;

		return [
			new Read(
				$hierarchy->getVariants(),
				(new DependentVariables\UserRoleAndHierarchyAsAttributes())
					->setHierarchy($hierarchy)
					->addSelectedVariablesAlias(
						[
							UserRoleAndHierarchy::SELF,
							UserRoleAndHierarchy::DEPARTMENT,
							UserRoleAndHierarchy::SUBDEPARTMENTS,
							UserRoleAndHierarchy::ALL,
						],
						Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
					)
				,
			),
			new Write(PermissionAttrPresets::switchAll(), new Toggler()),
		];
	}
	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$name = GetMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_SALETARGET');

		return [new EntityDTO('SALETARGET', $name, [], $this->permissions())];
	}
}

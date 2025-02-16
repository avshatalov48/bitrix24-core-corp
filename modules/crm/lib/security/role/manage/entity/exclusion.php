<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;

class Exclusion implements PermissionEntity
{
	private function permissions(): array
	{
		return [
			new Read(PermissionAttrPresets::switchAll(), new Toggler()),
			new Write(PermissionAttrPresets::switchAll(), new Toggler()),
		];
	}
	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$name = GetMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_EXCLUSION');

		return [new EntityDTO('EXCLUSION', $name, [], $this->permissions())];
	}
}

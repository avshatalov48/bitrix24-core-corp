<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;

class Button implements PermissionEntity
{
	private function permissions(): array
	{
		return [
			new Read(PermissionAttrPresets::switchAll()),
			new Write(PermissionAttrPresets::switchAll()),
		];
	}
	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$name = GetMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_BUTTON');

		return [new EntityDTO('BUTTON', $name, [], $this->permissions())];
	}
}

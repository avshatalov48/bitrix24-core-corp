<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;
use Bitrix\Main\Localization\Loc;

class WebForm implements PermissionEntity
{
	public const ENTITY_CODE = 'WEBFORM';

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
		$name = Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_WEBFORM');

		return [new EntityDTO(self::ENTITY_CODE, $name, [], $this->permissions())];
	}
}

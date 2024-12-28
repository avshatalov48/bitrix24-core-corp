<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\WriteConfig;
use Bitrix\Main\Localization\Loc;

final class WebFormConfig implements PermissionEntity
{
	public const CODE = 'WEBFORM_CONFIG';

	private function permissions(): array
	{
		return [
			new WriteConfig(PermissionAttrPresets::allowedYesNo()),
		];
	}

	public function make(): array
	{
		$name = Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_WEBFORM_CONFIG');

		return [
			new EntityDTO(self::CODE, $name, [], $this->permissions()),
		];
	}
}

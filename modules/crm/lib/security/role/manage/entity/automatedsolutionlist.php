<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\AutomatedSolution\Config;
use Bitrix\Crm\Security\Role\Manage\Permissions\AutomatedSolution\Write;
use Bitrix\Main\Localization\Loc;

final class AutomatedSolutionList implements PermissionEntity
{
	public const ENTITY_CODE = 'AUTOMATED_SOLUTION';

	public function permissions(): array
	{
		return [
			new Write(PermissionAttrPresets::allowedYesNo()),
			new Config(PermissionAttrPresets::allowedYesNo()),
		];
	}

	public function make(): array
	{
		$name = Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_AUTOMATED_SOLUTION_LIST_NAME');

		return [
			new EntityDTO(self::ENTITY_CODE, $name, [], $this->permissions()),
		];
	}
}

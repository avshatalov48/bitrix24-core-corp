<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\CopilotCallAssessment\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\CopilotCallAssessment\Write;
use Bitrix\Main\Localization\Loc;

class CopilotCallAssessment implements PermissionEntity
{
	private function permissions(): array
	{
		return [
			new Read(PermissionAttrPresets::allowedYesNo()),
			new Write(PermissionAttrPresets::allowedYesNo())
		];
	}

	public function make(): array
	{
		$name = Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_CRM_COPILOT_CALL_ASSESSMENT');

		return [
			new EntityDTO('CCA', $name, [], $this->permissions())
		];
	}
}
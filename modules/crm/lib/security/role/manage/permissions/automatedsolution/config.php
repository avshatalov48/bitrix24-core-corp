<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions\AutomatedSolution;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\BaseControlType;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\Toggler;
use Bitrix\Main\Localization\Loc;

final class Config extends Permission
{
	public function code(): string
	{
		return 'CONFIG';
	}

	public function name(): string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_AUTOMATED_SOLUTION_CONFIG');
	}

	public function canAssignPermissionToStages(): bool
	{
		return false;
	}

	protected function createDefaultControlType(): BaseControlType
	{
		return new Toggler();
	}
}

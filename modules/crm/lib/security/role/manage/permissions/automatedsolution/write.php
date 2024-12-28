<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions\AutomatedSolution;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\BaseControlType;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\Toggler;
use Bitrix\Main\Localization\Loc;

final class Write extends Permission
{
	public function code(): string
	{
		return 'WRITE';
	}

	public function name(): string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_AUTOMATED_SOLUTION_WRITE');
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

<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions\CopilotCallAssessment;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\BaseControlType;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\Toggler;
use Bitrix\Main\Localization\Loc;

class Read extends Permission
{
	public function code(): string
	{
		return 'READ';
	}

	public function name(): string
	{
		return (string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_COPILOT_CALL_ASSESSMENT_READ');
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

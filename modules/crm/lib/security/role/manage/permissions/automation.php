<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Automation extends Permission
{
	public function code(): string
	{
		return 'AUTOMATION';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_AUTOMATION');
	}

	public function canAssignPermissionToStages(): bool
	{
		return false;
	}

	public function sortOrder(): int
	{
		return 7;
	}
}
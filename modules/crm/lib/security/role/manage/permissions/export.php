<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Export extends Permission
{
	public function code(): string
	{
		return 'EXPORT';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_EXPORT');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 5;
	}
}

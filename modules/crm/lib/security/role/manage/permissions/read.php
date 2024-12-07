<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Read extends Permission
{
	public function code(): string
	{
		return 'READ';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_READ');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 1;
	}

}
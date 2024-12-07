<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Import extends Permission
{
	public function code(): string
	{
		return 'IMPORT';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_IMPORT');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 6;
	}
}
<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;


class Delete extends Permission
{
	public function code(): string
	{
		return 'DELETE';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_DELETE');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 4;
	}
}

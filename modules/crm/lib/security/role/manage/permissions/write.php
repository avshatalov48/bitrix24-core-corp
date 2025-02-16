<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Write extends Permission
{
	public function code(): string
	{
		return 'WRITE';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_WRITE');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 3;
	}

	public function getManagerDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_SELF;
	}
}

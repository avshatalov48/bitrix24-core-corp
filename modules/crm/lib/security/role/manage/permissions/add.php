<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

class Add extends Permission
{
	public function code(): string
	{
		return 'ADD';
	}

	public function name(): string
	{
		return GetMessage('CRM_SECURITY_ROLE_PERMS_HEAD_ADD');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): int
	{
		return 2;
	}

	public function getManagerDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_SELF;
	}
}
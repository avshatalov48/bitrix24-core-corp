<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Main\Localization\Loc;

class HideSum extends Permission
{
	public const INHERIT = 'INHERIT';

	public function code(): string
	{
		return 'HIDE_SUM';
	}

	public function name(): string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_HIDE_SUM_MSGVER_1');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function getDefaultAttribute(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE;
	}

	public function getDeputyDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}
}

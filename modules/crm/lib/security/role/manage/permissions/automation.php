<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Main\Localization\Loc;

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

	public function explanation(): ?string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_PERMS_EXPLANATION_AUTOMATION');
	}

	public function getHeadDefaultAttributeValue(): string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}

	public function getDeputyDefaultAttributeValue(): string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}
}

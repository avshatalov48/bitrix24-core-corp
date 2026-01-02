<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions\RepeatSale;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;
use Bitrix\Main\Localization\Loc;

class Write extends Permission
{
	public function code(): string
	{
		return 'WRITE';
	}

	public function name(): string
	{
		return (string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_REEPAT_SALE_WRITE');
	}

	public function canAssignPermissionToStages(): bool
	{
		return false;
	}

	protected function createDefaultControlMapper(): BaseControlMapper
	{
		return new Toggler();
	}
}

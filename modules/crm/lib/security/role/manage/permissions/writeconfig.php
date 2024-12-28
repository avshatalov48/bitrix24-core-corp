<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\BaseControlType;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\Toggler;

class WriteConfig extends Write
{
	public function name(): string
	{
		return GetMessage('CRM_PERMS_PERM_WRITE');
	}

	protected function createDefaultControlType(): BaseControlType
	{
		return new Toggler();
	}
}

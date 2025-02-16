<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;

class WriteConfig extends Write
{
	public function name(): string
	{
		return GetMessage('CRM_PERMS_PERM_WRITE');
	}

	protected function createDefaultControlMapper(): BaseControlMapper
	{
		return new Toggler();
	}
}

<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Main\Access\Permission\PermissionDictionary;

class WriteConfig extends Write
{
	public function name(): string
	{
		return GetMessage('CRM_PERMS_PERM_WRITE');
	}
}
<?php

namespace Bitrix\Sign\Access\Permission;

trait PermissionName
{
	public static function getName($permissionId): ?string
	{
		return parent::getName($permissionId);
	}
}
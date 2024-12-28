<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\Main\Access\Rule\AbstractRule;

final class UsersAccessEditRule extends AbstractRule
{
	public const VARIABLE_AVAILABLE = 1;

	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		if ($this->user->isAdmin())
		{
			return true;
		}

		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT);

		return $permissionValue === self::VARIABLE_AVAILABLE;
	}
}
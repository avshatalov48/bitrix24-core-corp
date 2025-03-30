<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\Rule\AbstractRule;

final class UserInviteRule extends AbstractRule
{
	public const VARIABLE_AVAILABLE = 1;

	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_USER_INVITE);

		return $permissionValue === self::VARIABLE_AVAILABLE;
	}
}
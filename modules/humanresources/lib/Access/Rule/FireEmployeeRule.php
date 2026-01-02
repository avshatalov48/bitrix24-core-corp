<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Model\UserModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\Rule\AbstractRule;

/**
 * Determines if user can fire another user
 */
final class FireEmployeeRule extends AbstractRule
{
	/**
	 * Permission value that determines if permission is granted
	 */
	public const VARIABLE_AVAILABLE = 1;

	/**
	 * @param \Bitrix\Main\Access\AccessibleItem|null $item - fired user of type UserModel
	 * @param $params
	 * @return bool
	 */
	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		if (!($item instanceof UserModel))
		{
			return false;
		}

		if ($this->user->getUserId() === $item->getId())
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$permissionValue = $this->user->getPermission(PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE);

		return $permissionValue === self::VARIABLE_AVAILABLE;
	}
}
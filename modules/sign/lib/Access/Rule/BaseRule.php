<?php

namespace Bitrix\Sign\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Contract;

class BaseRule extends AbstractRule
{
	/**
	 * check access permission
	 *
	 * @param AccessibleItem|null $item
	 * @param null $params
	 *
	 * @return bool
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if($this->user->isAdmin())
		{
			return true;
		}

		$action = ActionDictionary::getActionPermissionMap()[$params['action']];
		if($this->user->getPermission($action))
		{
			return true;
		}

		if (
			($item instanceof Contract\Access\AccessibleItemWithOwner)
			&& $this->checkAccessibleItemWithOwner((string)$params['action'], $item)
		)
		{
			return true;
		}

		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		$crmPermissionMap = PermissionDictionary::getCrmPermissionMap();
		if (!array_key_exists($action, $crmPermissionMap))
		{
			return false;
		}

		[$permission, $entity] = $crmPermissionMap[$action];
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();
		if (!method_exists($userPermissions, $permission))
		{
			return false;
		}

		$categoryId = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entity)
			?->getDefaultCategory()
			?->getId()
		;
		if ($permission === 'checkAddPermissions')
		{
			return $userPermissions->checkAddPermissions($entity, $categoryId);
		}
		$id = $item instanceof Contract\Item\ItemWithCrmId ? $item->getCrmId() : 0;

		return (bool)$userPermissions->{$permission}($entity, $id, $categoryId);
	}

	private function checkAccessibleItemWithOwner(string $action, Contract\Access\AccessibleItemWithOwner $item): bool
	{
		$user = $this->user;
		if (!$user instanceof UserModel)
		{
			return false;
		}
		if ($user->isAdmin())
		{
			return true;
		}

		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		$permissionId = ActionDictionary::getActionPermissionMap()[$action] ?? 0;
		$permissionValue = (new RolePermissionService())->getValueForPermission(
			$user->getRoles(),
			$permissionId,
		);
		if ($permissionValue === null)
		{
			return false;
		}

		$itemOwnerId = $item->getOwnerId();
		$userId = $user->getUserId();

		if ($permissionValue === \CCrmPerms::PERM_ALL)
		{
			return true;
		}
		if ($permissionValue === \CCrmPerms::PERM_SELF)
		{
			return $itemOwnerId === $userId;
		}
		if ($permissionValue === \CCrmPerms::PERM_SUBDEPARTMENT)
		{
			return in_array($userId, $user->getUserDepartmentMembers(true), true);
		}
		if ($permissionValue === \CCrmPerms::PERM_DEPARTMENT)
		{
			return in_array($userId, $user->getUserDepartmentMembers(), true);
		}

		return false;
	}
}

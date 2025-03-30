<?php

namespace Bitrix\Sign\Access\Rule;

use Bitrix\Crm;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Access\Document;
use Bitrix\Sign\Service\Container;

class BaseRule extends AbstractRule
{
	private const DOCUMENT_TO_TEMPLATE_PERMISSIONS_MAP = [
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD => SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ => SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE => SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
		PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE => SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
	];

	/**
	 * check access permission
	 *
	 * @param AccessibleItem|null $item
	 *
	 * @return bool
	 */
	public function execute(?AccessibleItem $item = null, $params = null): bool
	{
		if($this->user->isAdmin())
		{
			return true;
		}
		if (!is_array($params) || !array_key_exists('action', $params) || !is_string($params['action']))
		{
			return false;
		}
		$action = $params['action'];

		$permissionId = ActionDictionary::getPermissionIdByAction($action);
		if ($permissionId === null)
		{
			return false;
		}
		$permissionId = (string)$permissionId;

		$user = $this->user;
		if (!$user instanceof UserModel)
		{
			return false;
		}

		if ($item instanceof Document && $item->isTemplated() && $this->checkDocumentTemplateAccess($action, $item, $user))
		{
			return true;
		}

		if ($this->checkBinarySignPermission($permissionId))
		{
			return true;
		}
		if ($this->checkExtendedSignPermission($action, $item))
		{
			return true;
		}

		if (
			$item instanceof Contract\Access\AccessibleItemWithOwner
			&& $this->checkAccessibleItemWithOwner($action, $item)
		)
		{
			return true;
		}

		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		return $this->checkCrmEntityPermission($permissionId, $item);
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

		$itemOwnerId = $item->getOwnerId();

		return $this->checkSignPermission(ActionDictionary::getPermissionIdByAction($action), $user, $itemOwnerId);
	}

	private function checkDocumentTemplateAccess(string $action, Document $item, UserModel $user): bool
	{
		$permissionId = ActionDictionary::getPermissionIdByAction($action);
		$permissionId = self::DOCUMENT_TO_TEMPLATE_PERMISSIONS_MAP[$permissionId] ?? null;
		if ($permissionId === null)
		{
			return false;
		}

		$ownerId = $item->getOwnerId();

		return $this->checkSignPermission($permissionId, $user, $ownerId);
	}

	private function checkSignPermission(string|int $permissionId, UserModel $user, ?int $itemOwnerId = null): bool
	{
		$permissionValue = Container::instance()->getRolePermissionService()->getValueForPermission(
			$user->getRoles(),
			$permissionId,
		);
		if ($permissionValue === null || $permissionValue === \CCrmPerms::PERM_NONE)
		{
			return false;
		}
		if ($itemOwnerId === null)
		{
			return true;
		}

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
			return in_array($itemOwnerId, $user->getUserDepartmentMembers(true), true);
		}
		if ($permissionValue === \CCrmPerms::PERM_DEPARTMENT)
		{
			return in_array($itemOwnerId, $user->getUserDepartmentMembers(), true);
		}

		return false;
	}

	private function checkCrmEntityPermission(string $permissionId, ?AccessibleItem $item): bool
	{
		$crmPermissionMap = PermissionDictionary::getCrmPermissionMap();
		if (!array_key_exists($permissionId, $crmPermissionMap))
		{
			return false;
		}

		$container = Crm\Service\Container::getInstance();
		[$permission, $entity] = $crmPermissionMap[$permissionId];
		$userPermissions = $container->getUserPermissions($this->user->getUserId());
		if (!method_exists($userPermissions, $permission))
		{
			return false;
		}

		$categoryId = $container->getFactory($entity)
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

	private function checkExtendedSignPermission(string $action, ?AccessibleItem $item): bool
	{
		$user = $this->user;
		if (!$user instanceof UserModel)
		{
			return false;
		}

		return $this->checkSignPermission(
			ActionDictionary::getPermissionIdByAction($action),
			$user,
			$item?->getOwnerId(),
		);
	}

	private function checkBinarySignPermission(string $permissionId): ?int
	{
		return $this->user->getPermission($permissionId);
	}
}

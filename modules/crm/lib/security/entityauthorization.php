<?php
namespace Bitrix\Crm\Security;

use Bitrix\Crm;
use Bitrix\Crm\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;

/**
 * @deprecated
 * @see \Bitrix\Crm\Service\UserPermissions
 */
class EntityAuthorization
{
	public static function getCurrentUserID()
	{
		return \CCrmSecurityHelper::GetCurrentUserID();
	}

	public static function isAuthorized()
	{
		return \CCrmSecurityHelper::GetCurrentUser()->IsAuthorized();
	}

	public static function isAdmin($userID)
	{
		return \CCrmPerms::IsAdmin($userID);
	}

	public static function getUserPermissions($userID)
	{
		return \CCrmPerms::GetUserPermissions($userID);
	}

	/**
	 * @param int $permissionTypeID
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkPermission($permissionTypeID, $entityTypeID, $entityID = 0, $userPermissions = null)
	{
		if(!is_int($permissionTypeID))
		{
			$permissionTypeID = (int)$permissionTypeID;
		}

		if($permissionTypeID === EntityPermissionType::CREATE)
		{
			return self::checkCreatePermission($entityTypeID, $userPermissions);
		}
		elseif($permissionTypeID === EntityPermissionType::READ)
		{
			return self::checkReadPermission($entityTypeID, $entityID, $userPermissions);
		}
		elseif($permissionTypeID === EntityPermissionType::UPDATE)
		{
			return self::checkUpdatePermission($entityTypeID, $entityID, $userPermissions);
		}
		elseif($permissionTypeID === EntityPermissionType::DELETE)
		{
			return self::checkDeletePermission($entityTypeID, $entityID, $userPermissions);
		}

		return false;
	}

	/**
	 * @param int $entityTypeID
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkCreatePermission($entityTypeID, $userPermissions = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		return Container::getInstance()
			->getUserPermissions($userPermissions?->GetUserID())
			->entityType()
			->canAddItems($entityTypeID)
		;
	}

	/**
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @param \CCrmPerms|null $userPermissions
	 * @param array|null $params = [
	 *     'DEAL_CATEGORY_ID' => -1, //deal category
	 *     'CATEGORY_ID' => 0, //category for other types
	 * ];
	 *
	 * @return bool
	 */
	public static function checkReadPermission($entityTypeID, $entityID, $userPermissions = null, array $params = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$categoryId = $params['DEAL_CATEGORY_ID'] ?? $params['CATEGORY_ID'] ?? null;

		$userPermissions = Container::getInstance()->getUserPermissions($userPermissions?->GetUserID());
		if ($entityID > 0)
		{
			return $userPermissions
				->item()
				->canRead($entityTypeID, $entityID)
			;
		}

		if (is_null($categoryId))
		{
			return $userPermissions
				->entityType()
				->canReadItems($entityTypeID);
		}

		return $userPermissions
			->entityType()
			->canReadItemsInCategory($entityTypeID, $categoryId)
		;
	}

	/**
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkUpdatePermission($entityTypeID, $entityID, $userPermissions = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userPermissions?->GetUserID());
		if ($entityID > 0)
		{
			return $userPermissions
				->item()
				->canUpdate($entityTypeID, $entityID)
			;
		}
		return $userPermissions
			->entityType()
			->canUpdateItems($entityTypeID)
		;
	}

	/**
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkDeletePermission($entityTypeID, $entityID, $userPermissions = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userPermissions?->GetUserID());
		if ($entityID > 0)
		{
			return $userPermissions
				->item()
				->canDelete($entityTypeID, $entityID)
			;
		}

		return $userPermissions
			->entityType()
			->canDeleteItems($entityTypeID)
		;
	}
}

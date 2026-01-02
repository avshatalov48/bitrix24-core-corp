<?php

use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @deprecated
 * @see UserPermissions
 */
class CCrmAuthorizationHelper
{
	private static $USER_PERMISSIONS = null;

	public static function GetUserPermissions()
	{
		$userId = \Bitrix\Crm\Service\Container::getInstance()->getContext()->getUserId();

		if(!isset(self::$USER_PERMISSIONS[$userId]))
		{
			self::$USER_PERMISSIONS[$userId] = CCrmPerms::GetCurrentUserPermissions();
		}

		return self::$USER_PERMISSIONS[$userId];
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->entityType()->canAddItems() or Container::getInstance()->getUserPermissions()->entityType()->canAddItemsInCategory()
	 */
	public static function CheckCreatePermission($entityTypeName, $userPermissions = null)
	{
		return self::checkPermissionForType(UserPermissions::OPERATION_ADD, $entityTypeName, $userPermissions);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->item()->canUpdate()
	 */
	public static function CheckUpdatePermission($entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		return self::checkPermissionForTypeAndAttributes(UserPermissions::OPERATION_UPDATE, $entityTypeName, $entityID, $userPermissions, $entityAttrs);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->item()->canDelete() or Container::getInstance()->getUserPermissions()->entityType()->canDeleteItems()
	 */
	public static function CheckDeletePermission($entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		return self::checkPermissionForTypeAndAttributes(UserPermissions::OPERATION_DELETE, $entityTypeName, $entityID, $userPermissions, $entityAttrs);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->item()->canRead() or Container::getInstance()->getUserPermissions()->entityType()->canReadItems()
	 */
	public static function CheckReadPermission($entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		return self::checkPermissionForTypeAndAttributes(UserPermissions::OPERATION_READ, $entityTypeName, $entityID, $userPermissions, $entityAttrs);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->entityType()->canImportItems() or Container::getInstance()->getUserPermissions()->entityType()->canImportItemsInCategory()
	 */
	public static function CheckImportPermission($entityTypeName, $userPermissions = null)
	{
		return self::checkPermissionForType(UserPermissions::OPERATION_IMPORT, $entityTypeName, $userPermissions);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->entityType()->canExportItems() or Container::getInstance()->getUserPermissions()->entityType()-canExportItemsInCategory()
	 */
	public static function CheckExportPermission($entityTypeName, $userPermissions = null)
	{
		return self::checkPermissionForType(UserPermissions::OPERATION_EXPORT, $entityTypeName, $userPermissions);
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->isCrmAdmin()
	 */
	public static function CheckConfigurationUpdatePermission($userPermissions = null)
	{
		$userPermissions = $userPermissions ?? self::GetUserPermissions();

		return \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userPermissions->GetUserID())
			->isCrmAdmin()
		;
	}

	/**
	 * @deprecated
	 * Method is meaningless because always returns true
	 */
	public static function CheckConfigurationReadPermission($userPermissions = null)
	{
		return true;
	}

	/**
	 * @deprecated
	 * @see Container::getInstance()->getUserPermissions()->entityEditor()->canEditCommonView
	 */
	public static function CanEditOtherSettings()
	{
		return \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions()
			->entityEditor()
			->canEditCommonView()
		;
	}

	private static function checkPermissionForType(string $permissionType, $entityTypeName, $userPermissions = null): bool
	{
		$entityTypeName = (string)$entityTypeName;
		$userPermissions = $userPermissions ?? self::GetUserPermissions();

		return PermissionsManager::getInstance($userPermissions->GetUserID())
			->hasPermission($entityTypeName, $permissionType)
		;
	}

	private static function checkPermissionForTypeAndAttributes(string $permissionType, $entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null): bool
	{
		$entityTypeName = is_numeric($entityTypeName)
			? CCrmOwnerType::ResolveName($entityTypeName)
			: mb_strtoupper(strval($entityTypeName))
		;

		if (str_starts_with($entityTypeName, CCrmOwnerType::DealRecurringName)) // recurring deal permissions should be checked for real deals
		{
			$entityTypeName = str_replace(CCrmOwnerType::DealRecurringName,  CCrmOwnerType::DealName, $entityTypeName);
		}

		$entityID = (int)$entityID;
		$userPermissions = $userPermissions ?? self::GetUserPermissions();

		$userPermissionsService = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userPermissions->GetUserID())
		;

		if ($userPermissionsService->isAdmin())
		{
			return true;
		}

		$permissionsManager = PermissionsManager::getInstance($userPermissionsService->getUserId());
		if($entityID <= 0)
		{
			return $permissionsManager->hasPermission($entityTypeName, $permissionType);
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = \Bitrix\Crm\Security\Manager::resolveController($entityTypeName)
				->getPermissionAttributes($entityTypeName, [$entityID]);
		}
		$entityAttrs = $entityAttrs[$entityID] ?? [];

		return
			$permissionsManager->hasPermission($entityTypeName, $permissionType)
			&& $permissionsManager->doUserAttributesMatchesToEntityAttributes($entityTypeName, $permissionType, $entityAttrs)
		;
	}
}

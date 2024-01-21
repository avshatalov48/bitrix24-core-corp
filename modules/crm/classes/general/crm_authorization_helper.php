<?php
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

	public static function CheckCreatePermission($entityTypeName, $userPermissions = null)
	{
		$entityTypeName = strval($entityTypeName);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'ADD');
	}

	public static function CheckUpdatePermission($entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		$entityTypeName = strval($entityTypeName);
		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if (\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($userPermissions->GetUserID())->isAdmin())
		{
			return true;
		}

		if($entityID <= 0)
		{
			return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'WRITE');
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($entityTypeName, $entityID);
		}
		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'WRITE')
			&& $userPermissions->CheckEnityAccess($entityTypeName, 'WRITE', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array());
	}

	public static function CheckDeletePermission($entityTypeName, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		$entityTypeName = strval($entityTypeName);
		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if (\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($userPermissions->GetUserID())->isAdmin())
		{
			return true;
		}

		if($entityID <= 0)
		{
			return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'DELETE');
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($entityTypeName, $entityID);
		}

		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'DELETE')
			&& $userPermissions->CheckEnityAccess($entityTypeName, 'DELETE', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array());
	}

	public static function CheckReadPermission($entityType, $entityID, $userPermissions = null, $entityAttrs = null)
	{
		$entityTypeName = is_numeric($entityType)
			? CCrmOwnerType::ResolveName($entityType)
			: mb_strtoupper(strval($entityType));

		$entityID = intval($entityID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if (\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($userPermissions->GetUserID())->isAdmin())
		{
			return true;
		}

		if($entityID <= 0)
		{
			return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'READ');
		}

		if(!is_array($entityAttrs))
		{
			$entityAttrs = $userPermissions->GetEntityAttr($entityTypeName, $entityID);
		}

		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'READ')
			&& $userPermissions->CheckEnityAccess($entityTypeName, 'READ', isset($entityAttrs[$entityID]) ? $entityAttrs[$entityID] : array());
	}

	public static function CheckImportPermission($entityType, $userPermissions = null)
	{
		$entityTypeName = is_numeric($entityType)
			? CCrmOwnerType::ResolveName($entityType)
			: mb_strtoupper(strval($entityType));

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'IMPORT');
	}

	public static function CheckExportPermission($entityType, $userPermissions = null)
	{
		$entityTypeName = is_numeric($entityType)
			? CCrmOwnerType::ResolveName($entityType)
			: mb_strtoupper(strval($entityType));

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'EXPORT');
	}

	public static function CheckAutomationCreatePermission($entityType, $userPermissions = null)
	{
		$entityTypeName = is_numeric($entityType)
			? CCrmOwnerType::ResolveName($entityType)
			: mb_strtoupper(strval($entityType));

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return (
			static::CheckConfigurationUpdatePermission($userPermissions)
			||
			$userPermissions->HavePerm($entityTypeName, BX_CRM_PERM_ALL, 'AUTOMATION')
		);
	}

	public static function CheckConfigurationUpdatePermission($userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckConfigurationReadPermission($userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}

	public static function CanEditOtherSettings($user = null)
	{
		if(!($user !== null && ((get_class($user) === 'CUser') || ($user instanceof CUser))))
		{
			$user = CCrmSecurityHelper::GetCurrentUser();
		}

		return $user->CanDoOperation('edit_other_settings');
	}
}

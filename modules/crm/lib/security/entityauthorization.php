<?php
namespace Bitrix\Crm\Security;

use \Bitrix\Crm\Order;

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

	public static function checkCreatePermission($entityTypeID, $userPermissions = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return Order\Permissions\Order::checkCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderPayment)
		{
			return Order\Permissions\Payment::checkCreatePermission($userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return Order\Permissions\Shipment::checkCreatePermission($userPermissions);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissionEntityType = \CCrmPerms::ResolvePermissionEntityType($entityTypeName);

		return \CCrmAuthorizationHelper::CheckCreatePermission(
			$permissionEntityType,
			$userPermissions
		);
	}

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

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::CheckReadPermission(
				$entityID,
				$userPermissions,
				isset($params['DEAL_CATEGORY_ID']) ? (int)$params['DEAL_CATEGORY_ID'] : -1
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return Order\Permissions\Order::checkReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderPayment)
		{
			return Order\Permissions\Payment::checkReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return Order\Permissions\Shipment::checkReadPermission($entityID, $userPermissions);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissionEntityType = \CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID);

		return \CCrmAuthorizationHelper::CheckReadPermission(
			$permissionEntityType,
			$entityID,
			$userPermissions
		);
	}

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

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::CheckUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return Order\Permissions\Order::checkUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderPayment)
		{
			return Order\Permissions\Payment::checkUpdatePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return Order\Permissions\Shipment::checkUpdatePermission($entityID, $userPermissions);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissionEntityType = \CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID);

		return \CCrmAuthorizationHelper::CheckUpdatePermission(
			$permissionEntityType,
			$entityID,
			$userPermissions
		);
	}

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

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::CheckDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return Order\Permissions\Order::checkDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderPayment)
		{
			return Order\Permissions\Payment::checkDeletePermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return Order\Permissions\Shipment::checkDeletePermission($entityID, $userPermissions);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissionEntityType = \CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID);

		return \CCrmAuthorizationHelper::CheckDeletePermission(
			$permissionEntityType,
			$entityID,
			$userPermissions
		);
	}

	public static function getPermissionAttributes($entityTypeID, array $entityIDs)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		$entityIDs = array_unique(array_filter($entityIDs));

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::GetPermissionAttributes($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::GetPermissionAttributes($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::GetPermissionAttributes($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::GetPermissionAttributes($entityIDs);
		}

		$permissionEntityMap = array();
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		foreach($entityIDs as $entityID)
		{
			$permissionEntityType = \CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID);
			if(!isset($permissionEntityMap[$permissionEntityType]))
			{
				$permissionEntityMap[$permissionEntityType] = array();
			}
			$permissionEntityMap[$permissionEntityType][] = $entityID;
		}

		$results = array();
		foreach($permissionEntityMap as $permissionEntityType => $permissionEntityIDs)
		{
			$results += \CCrmPerms::GetEntityAttr($permissionEntityType, $permissionEntityIDs);
		}
		return $results;
	}
}
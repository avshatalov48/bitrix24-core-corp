<?php
namespace Bitrix\Crm\Authorization;

use Bitrix\Main;

class Authorization
{
	private static $userPermissions = null;
	public static function getUserPermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		return self::$userPermissions;
	}
	public static function checkReadPermission($entityTypeID, $entityID, $userPermissions = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		
		if($userPermissions === null)
		{
			$userPermissions = self::getUserPermissions();
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal || $entityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return \CCrmDeal::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckReadPermission($entityID, $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::CheckReadPermission($entityID, $userPermissions);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		throw new Main\NotSupportedException("The type '{$entityTypeName}' is not supported in current context.");
	}
}
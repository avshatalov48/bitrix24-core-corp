<?php
namespace Bitrix\Crm\UserField;

use Bitrix\Main;
use Bitrix\Crm;

class UserFieldManager
{
	/** @var \CCrmFields[]|null */
	private static $userFieldEntities = null;

	public static function resolveUserFieldEntityID($entityTypeID)
	{
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return \CCrmDeal::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::GetUserFieldEntityID();
		}

		return '';
	}
	public static function resolveEntityTypeID($userFieldEntityID)
	{
		if($userFieldEntityID === \CCrmLead::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Lead;
		}
		elseif($userFieldEntityID === \CCrmDeal::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Deal;
		}
		elseif($userFieldEntityID === \CCrmContact::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Contact;
		}
		elseif($userFieldEntityID === \CCrmCompany::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Company;
		}
		elseif($userFieldEntityID === \CCrmQuote::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Quote;
		}
		elseif($userFieldEntityID === \CCrmInvoice::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Invoice;
		}

		return \CCrmOwnerType::Undefined;
	}
	public static function getUserFieldEntity($entityTypeID)
	{
		global $USER_FIELD_MANAGER;

		$userFieldEntityID = self::resolveUserFieldEntityID($entityTypeID);
		if($userFieldEntityID === '')
		{
			return null;
		}

		if(self::$userFieldEntities === null)
		{
			self::$userFieldEntities = array();
		}

		if(isset(self::$userFieldEntities[$userFieldEntityID]))
		{
			return self::$userFieldEntities[$userFieldEntityID];
		}

		return (self::$userFieldEntities[$userFieldEntityID] = new \CCrmFields($USER_FIELD_MANAGER, $userFieldEntityID));
	}
	public static function prepareUserFieldSignature(array $fieldInfo, $value = null)
	{
		$signatureParams = array();

		if(isset($fieldInfo['ENTITY_ID']))
		{
			$signatureParams['ENTITY_ID'] = $fieldInfo['ENTITY_ID'];
		}

		if(isset($fieldInfo['FIELD']))
		{
			$signatureParams['FIELD'] = $fieldInfo['FIELD'];
		}
		elseif(isset($fieldInfo['FIELD_NAME']))
		{
			$signatureParams['FIELD'] = $fieldInfo['FIELD_NAME'];
		}

		if($value !== null)
		{
			$signatureParams['VALUE'] = $value;
		}
		elseif(isset($fieldInfo['VALUE']))
		{
			$signatureParams['VALUE'] = $fieldInfo['VALUE'];
		}
		
		return Main\UserField\Dispatcher::instance()->getSignature($signatureParams);
	}
}
<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class LeadConversionScheme
{
	const UNDEFINED = 0;
	const DEAL_CONTACT_COMPANY = 1;
	const DEAL_CONTACT = 2;
	const DEAL_COMPANY = 3;
	const DEAL = 4;
	const CONTACT_COMPANY = 5;
	const CONTACT = 6;
	const COMPANY = 7;

	const DEAL_CONTACT_COMPANY_NAME = 'DEAL_CONTACT_COMPANY';
	const DEAL_CONTACT_NAME = 'DEAL_CONTACT';
	const DEAL_COMPANY_NAME = 'DEAL_COMPANY';
	const DEAL_NAME = 'DEAL';
	const CONTACT_COMPANY_NAME = 'CONTACT_COMPANY';
	const CONTACT_NAME = 'CONTACT';
	const COMPANY_NAME = 'COMPANY';

	private static $allDescriptions = array();

	public static function isDefined($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return false;
		}

		$schemeID = (int)$schemeID;
		return $schemeID >= self::DEAL_CONTACT_COMPANY && $schemeID <= self::COMPANY;
	}

	/**
	 * Check if target entity type is supported.
	 * Result is depended on TYPE_ID options parameter.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array|null $options Options.
	 * @return bool
	 */
	public static function isTargetTypeSupported($entityTypeID, array $options = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::isDefined($entityTypeID))
		{
			return false;
		}

		$typeID = is_array($options) && isset($options['TYPE_ID'])
			? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		if($typeID === LeadConversionType::RETURNING_CUSTOMER)
		{
			return $entityTypeID === \CCrmOwnerType::Deal;
		}

		//$typeID === LeadConversionType::GENERAL
		return(
			$entityTypeID === \CCrmOwnerType::Deal ||
			$entityTypeID === \CCrmOwnerType::Contact ||
			$entityTypeID === \CCrmOwnerType::Company
		);
	}
	/**
	 * Check if scheme is supported.
	 * Result is depended on TYPE_ID options parameter.
	 * @param int $schemeID Scheme ID.
	 * @param array|null $options Options.
	 * @return bool
	 */
	public static function isSupported($schemeID, array $options = null)
	{
		if(!is_int($schemeID))
		{
			$schemeID = (int)$schemeID;
		}

		if(!self::isDefined($schemeID))
		{
			return false;
		}

		$typeID = is_array($options) && isset($options['TYPE_ID'])
			? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		if($typeID === LeadConversionType::RETURNING_CUSTOMER)
		{
			return $schemeID === self::DEAL;
		}

		//$typeID === LeadConversionType::GENERAL
		return true;
	}

	public static function getDefault(array $options = null)
	{
		$typeID = is_array($options) && isset($options['TYPE_ID'])
			? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		return $typeID === LeadConversionType::RETURNING_CUSTOMER
			? self::DEAL : self::DEAL_CONTACT;
	}

	/**
	 * @param LeadConversionConfig $config
	 * @return int
	 */
	public static function getCurrentOrDefault($config)
	{
		$schemeID = $config->getSchemeID();
		if($schemeID === LeadConversionScheme::UNDEFINED)
		{
			$schemeID = LeadConversionScheme::getDefault(array('TYPE_ID' => $config->getTypeID()));
		}
		return $schemeID;
	}

	public static function resolveName($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return '';
		}

		$schemeID = (int)$schemeID;
		if($schemeID <= 0)
		{
			return '';
		}

		switch($schemeID)
		{
			case self::DEAL_CONTACT_COMPANY:
				return self::DEAL_CONTACT_COMPANY_NAME;
			case self::DEAL_CONTACT:
				return self::DEAL_CONTACT_NAME;
			case self::DEAL_COMPANY:
				return self::DEAL_COMPANY_NAME;
			case self::DEAL:
				return self::DEAL_NAME;
			case self::CONTACT_COMPANY:
				return self::CONTACT_COMPANY_NAME;
			case self::CONTACT:
				return self::CONTACT_NAME;
			case self::COMPANY:
				return self::COMPANY_NAME;
			case self::UNDEFINED:
			default:
				return '';
		}
	}
	public static function getDescription($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return '';
		}

		$schemeID = (int)$schemeID;
		$descriptions = self::getAllDescriptions();
		return isset($descriptions[$schemeID]) ? $descriptions[$schemeID] : '';
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(empty(self::$allDescriptions[LANGUAGE_ID]))
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$allDescriptions[LANGUAGE_ID] = array(
				self::DEAL_CONTACT_COMPANY => GetMessage('CRM_LEAD_CONV_DEAL_CONTACT_COMPANY'),
				self::DEAL_CONTACT => GetMessage('CRM_LEAD_CONV_DEAL_CONTACT'),
				self::DEAL_COMPANY => GetMessage('CRM_LEAD_CONV_DEAL_COMPANY'),
				self::DEAL => GetMessage('CRM_LEAD_CONV_DEAL'),
				self::CONTACT_COMPANY => GetMessage('CRM_LEAD_CONV_CONTACT_COMPANY'),
				self::CONTACT => GetMessage('CRM_LEAD_CONV_CONTACT'),
				self::COMPANY => GetMessage('CRM_LEAD_CONV_COMPANY'),
			);
		}
		return self::$allDescriptions[LANGUAGE_ID];
	}
	/**
	* @return array Array of strings
	*/
	public static function getJavaScriptDescriptions($checkPermissions = false, array $options = null)
	{
		$result = array();
		$descriptions = self::getAllDescriptions();

		if(!$checkPermissions)
		{
			$isDealPermitted = true;
			$isContactPermitted = true;
			$isCompanyPermitted = true;
		}
		else
		{
			$permissions = null;
			if (is_array($options) && isset($options['PERMISSIONS']) && $options['PERMISSIONS'] instanceof \CCrmPerms)
			{
				$permissions = $options['PERMISSIONS'];
			}

			$flags = array();
			\CCrmLead::PrepareConversionPermissionFlags(0, $flags, $permissions);
			$isDealPermitted = $flags['CAN_CONVERT_TO_DEAL'];
			$isContactPermitted = $flags['CAN_CONVERT_TO_CONTACT'];
			$isCompanyPermitted = $flags['CAN_CONVERT_TO_COMPANY'];
		}

		$typeID = is_array($options) && isset($options['TYPE_ID'])
			? (int)$options['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$typeID = LeadConversionType::GENERAL;
		}

		if($typeID === LeadConversionType::RETURNING_CUSTOMER)
		{
			//Contact and Company are disabled for returning customer.
			if($isContactPermitted)
			{
				$isContactPermitted = false;
			}

			if($isCompanyPermitted)
			{
				$isCompanyPermitted = false;
			}
		}


		if($isDealPermitted && $isContactPermitted && $isCompanyPermitted)
		{
			foreach($descriptions as $schemeID => $description)
			{
				$result[self::resolveName($schemeID)] = $description;
			}
		}
		else
		{
			$schemes = array();
			if($isDealPermitted)
			{
				if($isContactPermitted && $isCompanyPermitted)
				{
					$schemes[] = self::DEAL_CONTACT_COMPANY;
				}
				if($isContactPermitted)
				{
					$schemes[] = self::DEAL_CONTACT;
				}
				if($isCompanyPermitted)
				{
					$schemes[] = self::DEAL_COMPANY;
				}
				$schemes[] = self::DEAL;
			}
			if($isContactPermitted && $isCompanyPermitted)
			{
				$schemes[] = self::CONTACT_COMPANY;
			}
			if($isContactPermitted)
			{
				$schemes[] = self::CONTACT;
			}
			if($isCompanyPermitted)
			{
				$schemes[] = self::COMPANY;
			}

			foreach($schemes as $schemeID)
			{
				$result[self::resolveName($schemeID)] = $descriptions[$schemeID];
			}
		}
		return $result;
	}

	public static function getEntityTypeIds(int $schemeId): array
	{
		if ($schemeId === static::DEAL_CONTACT_COMPANY)
		{
			return [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company];
		}
		if ($schemeId === static::DEAL_CONTACT)
		{
			return [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact];
		}
		if ($schemeId === static::DEAL_COMPANY)
		{
			return [\CCrmOwnerType::Deal, \CCrmOwnerType::Company];
		}
		if ($schemeId === static::DEAL)
		{
			return [\CCrmOwnerType::Deal];
		}
		if ($schemeId === static::CONTACT_COMPANY)
		{
			return [\CCrmOwnerType::Contact, \CCrmOwnerType::Company];
		}
		if ($schemeId === static::CONTACT)
		{
			return [\CCrmOwnerType::Contact];
		}
		if ($schemeId === static::COMPANY)
		{
			return [\CCrmOwnerType::Company];
		}

		return [];
	}
}

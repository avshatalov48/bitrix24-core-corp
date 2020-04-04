<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Main;
use Bitrix\Crm\CommunicationType;

class DuplicateIndexType
{
	const UNDEFINED = 				0x0;
	const PERSON = 					0x1;
	const ORGANIZATION = 			0x2;
	const COMMUNICATION_PHONE = 	0x4;
	const COMMUNICATION_EMAIL = 	0x8;
	const COMMUNICATION_FACEBOOK = 	0x10;
	const COMMUNICATION_TELEGRAM = 	0x20;
	const COMMUNICATION_VK = 		0x40;
	const COMMUNICATION_SKYPE = 	0x80;
	const COMMUNICATION_BITRIX24 = 	0x100;
	const COMMUNICATION_OPENLINE = 	0x200;
	const COMMUNICATION_VIBER = 	0x800000;

	const RQ_INN = 					0x400;
	const RQ_OGRN = 				0x800;
	const RQ_OGRNIP = 				0x1000;
	const RQ_BIN = 					0x2000;
	const RQ_EDRPOU = 				0x4000;
	const RQ_VAT_ID = 				0x8000;
	//       reserved 65536
	//       reserved 131072
	//       reserved 262144
	//       reserved 524288
	const RQ_ACC_NUM = 				0x100000;
	const RQ_IBAN = 				0x200000;
	const RQ_IIK = 					0x400000;

	const BANK_DETAIL = 			0x700000; 	/*  RQ_ACC_NUM|RQ_IBAN|RQ_IIK  */
	const REQUISITE = 				0xFC00; 	/*  RQ_INN|RQ_OGRN|RQ_OGRNIP|RQ_BIN|RQ_EDRPOU|RQ_VAT_ID  */
	const COMMUNICATION = 			0x8003FC; 	/*  COMMUNICATION_PHONE|COMMUNICATION_EMAIL|COMMUNICATION_FACEBOOK|COMMUNICATION_TELEGRAM|COMMUNICATION_VK|COMMUNICATION_SKYPE|COMMUNICATION_BITRIX24|COMMUNICATION_OPENLINE|COMMUNICATION_VIBER */
	const DENOMINATION = 			0x3; 		/*  PERSON|ORGANIZATION  */
	const ALL = 					0xF0FFFF;	/*  PERSON|ORGANIZATION|COMMUNICATION_PHONE|COMMUNICATION_EMAIL|COMMUNICATION_FACEBOOK|COMMUNICATION_TELEGRAM|COMMUNICATION_VK|COMMUNICATION_SKYPE|COMMUNICATION_BITRIX24|COMMUNICATION_OPENLINE|COMMUNICATION_VIBER|RQ_INN|RQ_OGRN|RQ_OGRNIP|RQ_BIN|RQ_EDRPOU|RQ_VAT_ID|RQ_ACC_NUM|RQ_IBAN|RQ_IIK  */

	const PERSON_NAME = 'PERSON';
	const ORGANIZATION_NAME = 'ORGANIZATION';
	const COMMUNICATION_PHONE_NAME = 'COMMUNICATION_PHONE';
	const COMMUNICATION_EMAIL_NAME = 'COMMUNICATION_EMAIL';
	const COMMUNICATION_FACEBOOK_NAME = 'COMMUNICATION_FACEBOOK';
	const COMMUNICATION_TELEGRAM_NAME = 'COMMUNICATION_TELEGRAM';
	const COMMUNICATION_VK_NAME = 'COMMUNICATION_VK';
	const COMMUNICATION_SKYPE_NAME = 'COMMUNICATION_SKYPE';
	const COMMUNICATION_BITRIX24_NAME = 'COMMUNICATION_BITRIX24';
	const COMMUNICATION_OPENLINE_NAME = 'COMMUNICATION_OPENLINE';
	const COMMUNICATION_VIBER_NAME = 'COMMUNICATION_VIBER';
	const RQ_INN_NAME = 'RQ_INN';
	const RQ_OGRN_NAME = 'RQ_OGRN';
	const RQ_OGRNIP_NAME = 'RQ_OGRNIP';
	const RQ_BIN_NAME = 'RQ_BIN';
	const RQ_EDRPOU_NAME = 'RQ_EDRPOU';
	const RQ_VAT_ID_NAME = 'RQ_VAT_ID';
	const RQ_ACC_NUM_NAME = 'RQ_ACC_NUM';
	const RQ_IBAN_NAME = 'RQ_IBAN';
	const RQ_IIK_NAME = 'RQ_IIK';

	const DEFAULT_SCOPE = '';

	private static $allDescriptions = array();

	/**
	 * Check if type defined
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return $typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_PHONE
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_FACEBOOK
			|| $typeID === self::COMMUNICATION_TELEGRAM
			|| $typeID === self::COMMUNICATION_VK
			|| $typeID === self::COMMUNICATION_SKYPE
			|| $typeID === self::COMMUNICATION_BITRIX24
			|| $typeID === self::COMMUNICATION_OPENLINE
			|| $typeID === self::COMMUNICATION_VIBER
			|| $typeID === self::RQ_INN
			|| $typeID === self::RQ_OGRN
			|| $typeID === self::RQ_OGRNIP
			|| $typeID === self::RQ_BIN
			|| $typeID === self::RQ_EDRPOU
			|| $typeID === self::RQ_VAT_ID
			|| $typeID === self::RQ_ACC_NUM
			|| $typeID === self::RQ_IBAN
			|| $typeID === self::RQ_IIK
			|| $typeID === self::DENOMINATION
			|| $typeID === self::COMMUNICATION
			|| $typeID === self::REQUISITE
			|| $typeID === self::BANK_DETAIL
			|| $typeID === self::ALL;
	}
	/**
	 * Resolve type name by ID.
	 * @param int $typeID Type ID.
	 * @return string
	 */
	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = (int)$typeID;
		if($typeID <= 0)
		{
			return '';
		}

		$results = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$results[] = self::PERSON_NAME;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$results[] = self::ORGANIZATION_NAME;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$results[] = self::COMMUNICATION_PHONE_NAME;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$results[] = self::COMMUNICATION_EMAIL_NAME;
		}
		if(($typeID & self::COMMUNICATION_FACEBOOK) !== 0)
		{
			$results[] = self::COMMUNICATION_FACEBOOK_NAME;
		}
		if(($typeID & self::COMMUNICATION_TELEGRAM) !== 0)
		{
			$results[] = self::COMMUNICATION_TELEGRAM_NAME;
		}
		if(($typeID & self::COMMUNICATION_VK) !== 0)
		{
			$results[] = self::COMMUNICATION_VK_NAME;
		}
		if(($typeID & self::COMMUNICATION_SKYPE) !== 0)
		{
			$results[] = self::COMMUNICATION_SKYPE_NAME;
		}
		if(($typeID & self::COMMUNICATION_BITRIX24) !== 0)
		{
			$results[] = self::COMMUNICATION_BITRIX24_NAME;
		}
		if(($typeID & self::COMMUNICATION_OPENLINE) !== 0)
		{
			$results[] = self::COMMUNICATION_OPENLINE_NAME;
		}
		if(($typeID & self::COMMUNICATION_VIBER) !== 0)
		{
			$results[] = self::COMMUNICATION_VIBER_NAME;
		}
		if(($typeID & self::RQ_INN) !== 0)
		{
			$results[] = self::RQ_INN_NAME;
		}
		if(($typeID & self::RQ_OGRN) !== 0)
		{
			$results[] = self::RQ_OGRN_NAME;
		}
		if(($typeID & self::RQ_OGRNIP) !== 0)
		{
			$results[] = self::RQ_OGRNIP_NAME;
		}
		if(($typeID & self::RQ_BIN) !== 0)
		{
			$results[] = self::RQ_BIN_NAME;
		}
		if(($typeID & self::RQ_EDRPOU) !== 0)
		{
			$results[] = self::RQ_EDRPOU_NAME;
		}
		if(($typeID & self::RQ_VAT_ID) !== 0)
		{
			$results[] = self::RQ_VAT_ID_NAME;
		}
		if(($typeID & self::RQ_ACC_NUM) !== 0)
		{
			$results[] = self::RQ_ACC_NUM_NAME;
		}
		if(($typeID & self::RQ_IBAN) !== 0)
		{
			$results[] = self::RQ_IBAN_NAME;
		}
		if(($typeID & self::RQ_IIK) !== 0)
		{
			$results[] = self::RQ_IIK_NAME;
		}
		return implode('|', $results);
	}
	/**
	 * Resolve type ID by name.
	 * @param string $typeName Type name (single or multiple).
	 * @return int
	 */
	public static function resolveID($typeName)
	{
		$typeID = self::innerResolveID($typeName);
		if($typeID !== self::UNDEFINED)
		{
			return $typeID;
		}

		if(strpos($typeName, '|') >= 0)
		{
			$typeNames = explode('|', $typeName);
			foreach($typeNames as $name)
			{
				$typeID |= self::innerResolveID(trim($name));
			}
		}
		return $typeID;
	}
	/**
	 * Resolve type ID by name.
	 * @param string $typeName Type name (only single names are accepted).
	 * @return int
	 */
	private static function innerResolveID($typeName)
	{
		if(!is_string($typeName))
		{
			return self::UNDEFINED;
		}

		$typeName = strtoupper(trim($typeName));
		if($typeName === '')
		{
			return self::UNDEFINED;
		}

		if($typeName === self::PERSON_NAME)
		{
			return self::PERSON;
		}
		if($typeName === self::ORGANIZATION_NAME)
		{
			return self::ORGANIZATION;
		}
		if($typeName === self::COMMUNICATION_PHONE_NAME)
		{
			return self::COMMUNICATION_PHONE;
		}
		if($typeName ===  self::COMMUNICATION_EMAIL_NAME)
		{
			return self::COMMUNICATION_EMAIL;
		}
		if($typeName ===  self::COMMUNICATION_FACEBOOK_NAME)
		{
			return self::COMMUNICATION_FACEBOOK;
		}
		if($typeName ===  self::COMMUNICATION_TELEGRAM_NAME)
		{
			return self::COMMUNICATION_TELEGRAM;
		}
		if($typeName ===  self::COMMUNICATION_VK_NAME)
		{
			return self::COMMUNICATION_VK;
		}
		if($typeName ===  self::COMMUNICATION_SKYPE_NAME)
		{
			return self::COMMUNICATION_SKYPE;
		}
		if($typeName ===  self::COMMUNICATION_BITRIX24_NAME)
		{
			return self::COMMUNICATION_BITRIX24;
		}
		if($typeName ===  self::COMMUNICATION_OPENLINE_NAME)
		{
			return self::COMMUNICATION_OPENLINE;
		}
		if($typeName ===  self::COMMUNICATION_VIBER_NAME)
		{
			return self::COMMUNICATION_VIBER;
		}
		if($typeName ===  self::RQ_INN_NAME)
		{
			return self::RQ_INN;
		}
		if($typeName ===  self::RQ_OGRN_NAME)
		{
			return self::RQ_OGRN;
		}
		if($typeName ===  self::RQ_OGRNIP_NAME)
		{
			return self::RQ_OGRNIP;
		}
		if($typeName ===  self::RQ_BIN_NAME)
		{
			return self::RQ_BIN;
		}
		if($typeName ===  self::RQ_EDRPOU_NAME)
		{
			return self::RQ_EDRPOU;
		}
		if($typeName ===  self::RQ_VAT_ID_NAME)
		{
			return self::RQ_VAT_ID;
		}
		if($typeName ===  self::RQ_ACC_NUM_NAME)
		{
			return self::RQ_ACC_NUM;
		}
		if($typeName ===  self::RQ_IBAN_NAME)
		{
			return self::RQ_IBAN;
		}
		if($typeName ===  self::RQ_IIK_NAME)
		{
			return self::RQ_IIK;
		}

		return self::UNDEFINED;
	}
	/**
	 * Get all type descriptions
	 * @return array
	 */
	public static function getAllDescriptions()
	{
		if(!self::$allDescriptions[LANGUAGE_ID])
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$allDescriptions[LANGUAGE_ID] = array(
				self::PERSON => array('' => GetMessage('CRM_DUP_INDEX_TYPE_PERSON')),
				self::ORGANIZATION => array('' => GetMessage('CRM_DUP_INDEX_TYPE_ORGANIZATION')),
				self::COMMUNICATION_PHONE => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_PHONE')),
				self::COMMUNICATION_EMAIL => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_EMAIL')),
				self::COMMUNICATION_FACEBOOK => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_FACEBOOK')),
				self::COMMUNICATION_TELEGRAM => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_TELEGRAM')),
				self::COMMUNICATION_VK => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_VK')),
				self::COMMUNICATION_SKYPE => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_SKYPE')),
				self::COMMUNICATION_BITRIX24 => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_BITRIX24')),
				self::COMMUNICATION_OPENLINE => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_OPENLINE')),
				self::COMMUNICATION_VIBER => array('' => GetMessage('CRM_DUP_INDEX_TYPE_COMM_VIBER'))
			);

			$requisite = new EntityRequisite();
			foreach ($requisite->getDuplicateCriterionFieldsDescriptions() as $fieldName => $descriptions)
			{
				$indexType = self::resolveID($fieldName);
				if (!is_array(self::$allDescriptions[$indexType]))
				{
					self::$allDescriptions[LANGUAGE_ID][$indexType] = array();
				}
				foreach ($descriptions as $scope => $description)
				{
					self::$allDescriptions[LANGUAGE_ID][$indexType][$scope] = $description;
				}
			}

			$bankDetail = new EntityBankDetail();
			foreach ($bankDetail->getDuplicateCriterionFieldsDescriptions() as $fieldName => $descriptions)
			{
				$indexType = self::resolveID($fieldName);
				if (!is_array(self::$allDescriptions[$indexType]))
				{
					self::$allDescriptions[LANGUAGE_ID][$indexType] = array();
				}
				foreach ($descriptions as $scope => $description)
				{
					self::$allDescriptions[LANGUAGE_ID][$indexType][$scope] = $description;
				}
			}
		}

		return self::$allDescriptions[LANGUAGE_ID];
	}
	/**
	 * Check if name is not multiple.
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isSingle($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return ($typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_PHONE
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_FACEBOOK
			|| $typeID === self::COMMUNICATION_TELEGRAM
			|| $typeID === self::COMMUNICATION_VK
			|| $typeID === self::COMMUNICATION_SKYPE
			|| $typeID === self::COMMUNICATION_BITRIX24
			|| $typeID === self::COMMUNICATION_OPENLINE
			|| $typeID === self::COMMUNICATION_VIBER
			|| $typeID === self::RQ_INN
			|| $typeID === self::RQ_OGRN
			|| $typeID === self::RQ_OGRNIP
			|| $typeID === self::RQ_BIN
			|| $typeID === self::RQ_EDRPOU
			|| $typeID === self::RQ_VAT_ID
			|| $typeID === self::RQ_ACC_NUM
			|| $typeID === self::RQ_IBAN
			|| $typeID === self::RQ_IIK
		);
	}
	/**
	 * Convert type list to multiple type ID.
	 * @param array $typeIDs Type ID list.
	 * @return int
	 */
	public static function joinType(array $typeIDs)
	{
		$result = 0;
		foreach($typeIDs as $typeID)
		{
			$result |= $typeID;
		}
		return $result;
	}
	/**
	 * Convert multiple type ID to type list.
	 * @param int $typeID Type ID.
	 * @return array
	 */
	public static function splitType($typeID)
	{
		$typeID = intval($typeID);

		$result = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$result[] = self::PERSON;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$result[] = self::ORGANIZATION;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$result[] = self::COMMUNICATION_PHONE;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$result[] = self::COMMUNICATION_EMAIL;
		}
		if(($typeID & self::COMMUNICATION_FACEBOOK) !== 0)
		{
			$result[] = self::COMMUNICATION_FACEBOOK;
		}
		if(($typeID & self::COMMUNICATION_TELEGRAM) !== 0)
		{
			$result[] = self::COMMUNICATION_TELEGRAM;
		}
		if(($typeID & self::COMMUNICATION_VK) !== 0)
		{
			$result[] = self::COMMUNICATION_VK;
		}
		if(($typeID & self::COMMUNICATION_SKYPE) !== 0)
		{
			$result[] = self::COMMUNICATION_SKYPE;
		}
		if(($typeID & self::COMMUNICATION_BITRIX24) !== 0)
		{
			$result[] = self::COMMUNICATION_BITRIX24;
		}
		if(($typeID & self::COMMUNICATION_OPENLINE) !== 0)
		{
			$result[] = self::COMMUNICATION_OPENLINE;
		}
		if(($typeID & self::COMMUNICATION_VIBER) !== 0)
		{
			$result[] = self::COMMUNICATION_VIBER;
		}
		if(($typeID & self::RQ_INN) !== 0)
		{
			$result[] = self::RQ_INN;
		}
		if(($typeID & self::RQ_OGRN) !== 0)
		{
			$result[] = self::RQ_OGRN;
		}
		if(($typeID & self::RQ_OGRNIP) !== 0)
		{
			$result[] = self::RQ_OGRNIP;
		}
		if(($typeID & self::RQ_BIN) !== 0)
		{
			$result[] = self::RQ_BIN;
		}
		if(($typeID & self::RQ_EDRPOU) !== 0)
		{
			$result[] = self::RQ_EDRPOU;
		}
		if(($typeID & self::RQ_VAT_ID) !== 0)
		{
			$result[] = self::RQ_VAT_ID;
		}
		if(($typeID & self::RQ_ACC_NUM) !== 0)
		{
			$result[] = self::RQ_ACC_NUM;
		}
		if(($typeID & self::RQ_IBAN) !== 0)
		{
			$result[] = self::RQ_IBAN;
		}
		if(($typeID & self::RQ_IIK) !== 0)
		{
			$result[] = self::RQ_IIK;
		}

		return $result;
	}
	/**
	 * Get supported types for specified entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return array
	 * @deprecated since 16.2.0
	 * @see: DuplicateManager::getSupportedDedupeTypes
	 */
	public static function getSupportedTypes($entityTypeID)
	{
		return DuplicateManager::getSupportedDedupeTypes($entityTypeID);
	}
	public static function checkScopeValue($scope)
	{
		if (!is_string($scope))
			return false;
		if ($scope === self::DEFAULT_SCOPE)
			return true;
		if (preg_match('/^CY_(\d{3})$/', $scope))
			return true;

		return false;
	}
	public static function getAllScopeTitles()
	{
		$result = array(self::DEFAULT_SCOPE => '');

		$countryList = EntityPreset::getCountryList();
		foreach (EntityRequisite::getAllowedRqFieldCountries() as $countryId)
		{
			$scope = EntityRequisite::formatDuplicateCriterionScope($countryId);
			$result[$scope] = isset($countryList[$countryId]) ? $countryList[$countryId] : $scope;
		}

		return $result;
	}
	public static function getPreferredScope()
	{
		$result = DuplicateIndexType::DEFAULT_SCOPE;

		$countryId = EntityPreset::getCurrentCountryId();
		if ($countryId > 0)
			$result = EntityRequisite::formatDuplicateCriterionScope($countryId);

		return $result;
	}
	/**
	 * Try to convert communication type into duplicate index type
	 * @param int $commTypeID Source communication type.
	 * @return integer
	 */
	public static function convertFromCommunicationType($commTypeID)
	{
		$commTypeID = (int)$commTypeID;
		if($commTypeID === CommunicationType::PHONE)
		{
			return self::COMMUNICATION_PHONE;
		}
		elseif($commTypeID === CommunicationType::EMAIL)
		{
			return self::COMMUNICATION_EMAIL;
		}
		elseif($commTypeID === CommunicationType::FACEBOOK)
		{
			return self::COMMUNICATION_FACEBOOK;
		}
		elseif($commTypeID === CommunicationType::TELEGRAM)
		{
			return self::COMMUNICATION_TELEGRAM;
		}
		elseif($commTypeID === CommunicationType::VK)
		{
			return self::COMMUNICATION_VK;
		}
		elseif($commTypeID === CommunicationType::SKYPE)
		{
			return self::COMMUNICATION_SKYPE;
		}
		elseif($commTypeID === CommunicationType::BITRIX24)
		{
			return self::COMMUNICATION_BITRIX24;
		}
		elseif($commTypeID === CommunicationType::OPENLINE)
		{
			return self::COMMUNICATION_OPENLINE;
		}
		elseif($commTypeID === CommunicationType::VIBER)
		{
			return self::COMMUNICATION_VIBER;
		}
		return self::UNDEFINED;
	}	/**
	 * Try to convert duplicate index type into communication type
	 * @param int $typeID Duplicate index type.
	 * @return integer
	 */
	public static function convertToCommunicationType($typeID)
	{
		$typeID = (int)$typeID;
		if($typeID === self::COMMUNICATION_PHONE)
		{
			return CommunicationType::PHONE;
		}
		elseif($typeID === self::COMMUNICATION_EMAIL)
		{
			return CommunicationType::EMAIL;
		}
		elseif($typeID === self::COMMUNICATION_FACEBOOK)
		{
			return CommunicationType::FACEBOOK;
		}
		elseif($typeID === self::COMMUNICATION_TELEGRAM)
		{
			return CommunicationType::TELEGRAM;
		}
		elseif($typeID === self::COMMUNICATION_VK)
		{
			return CommunicationType::VK;
		}
		elseif($typeID === self::COMMUNICATION_SKYPE)
		{
			return CommunicationType::SKYPE;
		}
		elseif($typeID === self::COMMUNICATION_BITRIX24)
		{
			return CommunicationType::BITRIX24;
		}
		elseif($typeID === self::COMMUNICATION_OPENLINE)
		{
			return CommunicationType::OPENLINE;
		}
		elseif($typeID === self::COMMUNICATION_VIBER)
		{
			return CommunicationType::VIBER;
		}
		return CommunicationType::UNDEFINED;
	}
}
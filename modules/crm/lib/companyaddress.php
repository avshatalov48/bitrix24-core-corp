<?php
namespace Bitrix\Crm;
use Bitrix\Main;

class CompanyAddress extends EntityAddress
{
	private static $fieldMaps = array();
	private static $invertedFieldMaps = array();
	/**
	* @return \CCrmEntityListBuilder
	*/
	protected static function createEntityListBuilder()
	{
		return \CCrmCompany::CreateListBuilder();
	}

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	* @return array
	*/
	protected static function getSupportedTypeIDs()
	{
		return array(EntityAddress::Primary, EntityAddress::Registered);
	}

	/**
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		if(!isset(self::$fieldMaps[$typeID]))
		{
			if($typeID === EntityAddress::Registered)
			{
				self::$fieldMaps[$typeID] = array(
					'ADDRESS_1' => 'REG_ADDRESS',
					'ADDRESS_2' => 'REG_ADDRESS_2',
					'CITY' => 'REG_ADDRESS_CITY',
					'POSTAL_CODE' => 'REG_ADDRESS_POSTAL_CODE',
					'REGION' => 'REG_ADDRESS_REGION',
					'PROVINCE' => 'REG_ADDRESS_PROVINCE',
					'COUNTRY' => 'REG_ADDRESS_COUNTRY',
					'COUNTRY_CODE' => 'REG_ADDRESS_COUNTRY_CODE'
				);
			}
			else
			{
				self::$fieldMaps[$typeID] = array(
					'ADDRESS_1' => 'ADDRESS',
					'ADDRESS_2' => 'ADDRESS_2',
					'CITY' => 'ADDRESS_CITY',
					'POSTAL_CODE' => 'ADDRESS_POSTAL_CODE',
					'REGION' => 'ADDRESS_REGION',
					'PROVINCE' => 'ADDRESS_PROVINCE',
					'COUNTRY' => 'ADDRESS_COUNTRY',
					'COUNTRY_CODE' => 'ADDRESS_COUNTRY_CODE'
				);
			}
		}

		return self::$fieldMaps[$typeID];
	}

	/**
	* @return array
	*/
	protected static function getInvertedFieldMap($typeID)
	{
		if(!isset(self::$invertedFieldMaps[$typeID]))
		{
			self::$invertedFieldMaps[$typeID] = array_flip(self::getFieldMap($typeID));
		}
		return self::$invertedFieldMaps[$typeID];
	}

	/**
	* @return int
	*/
	public static function resolveEntityFieldTypeID($fieldName, array $aliases = null)
	{
		if(is_array($aliases) && isset($aliases[$fieldName]))
		{
			$fieldName = $aliases[$fieldName];
		}

		return stripos($fieldName, 'REG_') === 0 ? EntityAddress::Registered : EntityAddress::Primary;
	}

	/**
	 * Remove entity addresses
	 * @param array $entityID Entity ID.
	 * @return void
	*/
	public static function deleteByEntityId($entityID)
	{
		EntityAddress::deleteByEntity(\CCrmOwnerType::Company, $entityID);
	}
}
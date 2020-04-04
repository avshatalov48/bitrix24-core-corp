<?php
namespace Bitrix\Crm;
use Bitrix\Main;

class LeadAddress extends EntityAddress
{
	private static $fieldMap = null;
	private static $invertedFieldMap = null;
	/**
	* @return \CCrmEntityListBuilder
	*/
	protected static function createEntityListBuilder()
	{
		return \CCrmLead::CreateListBuilder();
	}

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	/**
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		if(self::$fieldMap === null)
		{
			self::$fieldMap = array(
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

		return self::$fieldMap;
	}

	/**
	* @return array
	*/
	protected static function getInvertedFieldMap($typeID)
	{
		if(self::$invertedFieldMap === null)
		{
			self::$invertedFieldMap = array_flip(self::getFieldMap($typeID));
		}
		return self::$invertedFieldMap;
	}

	/**
	* @return int
	*/
	public static function resolveEntityFieldTypeID($fieldName, array $aliases = null)
	{
		return EntityAddress::Primary;
	}

	/**
	 * Remove entity addresses
	 * @param array $entityID Entity ID.
	 * @return void
	*/
	public static function deleteByEntityId($entityID)
	{
		EntityAddress::deleteByEntity(\CCrmOwnerType::Lead, $entityID);
	}
}
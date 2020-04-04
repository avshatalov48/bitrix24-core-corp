<?php
namespace Bitrix\Crm\Integration\Channel;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ChannelType
{
	const UNDEFINED = 0;
	const EMAIL = 2;
	const VOXIMPLANT = 3;
	const IMOPENLINE = 4;
	const WEBFORM = 5;
	const SITEBUTTON = 6;
	const LEAD_IMPORT = 7;
	const EXTERNAL_CUSTOM = 8;
	const EXTERNAL_BITRIX = 9;
	const EXTERNAL_ONE_C = 10;
	const EXTERNAL_WORDPRESS = 11;
	const EXTERNAL_JOOMLA = 12;
	const EXTERNAL_DRUPAL = 13;
	const EXTERNAL_MAGENTO = 14;

	const EMAIL_NAME = 'EMAIL';
	const VOXIMPLANT_NAME = 'VOXIMPLANT';
	const IMOPENLINE_NAME = 'IMOPENLINE';
	const WEBFORM_NAME = 'WEBFORM';
	const SITEBUTTON_NAME = 'SITEBUTTON';
	const LEAD_IMPORT_NAME = 'LEAD_IMPORT';

	const EXTERNAL_CUSTOM_NAME = 'EXTERNAL_CUSTOM';
	const EXTERNAL_BITRIX_NAME = 'EXTERNAL_BITRIX';
	const EXTERNAL_ONE_C_NAME = 'EXTERNAL_ONE_C';
	const EXTERNAL_WORDPRESS_NAME = 'EXTERNAL_WORDPRESS';
	const EXTERNAL_JOOMLA_NAME = 'EXTERNAL_JOOMLA';
	const EXTERNAL_DRUPAL_NAME = 'EXTERNAL_DRUPAL';
	const EXTERNAL_MAGENTO_NAME = 'EXTERNAL_MAGENTO';

	/**
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		return $typeID >= self::EMAIL_NAME && $typeID <= self::EXTERNAL_MAGENTO;
	}
	/**
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
		if($typeID === self::EXTERNAL_CUSTOM)
		{
			return self::EXTERNAL_CUSTOM_NAME;
		}
		if($typeID === self::EXTERNAL_BITRIX)
		{
			return self::EXTERNAL_BITRIX_NAME;
		}
		if($typeID === self::EXTERNAL_ONE_C)
		{
			return self::EXTERNAL_ONE_C_NAME;
		}
		if($typeID === self::EXTERNAL_WORDPRESS)
		{
			return self::EXTERNAL_WORDPRESS_NAME;
		}
		if($typeID === self::EXTERNAL_JOOMLA)
		{
			return self::EXTERNAL_JOOMLA_NAME;
		}
		if($typeID === self::EXTERNAL_DRUPAL)
		{
			return self::EXTERNAL_DRUPAL_NAME;
		}
		if($typeID === self::EXTERNAL_MAGENTO)
		{
			return self::EXTERNAL_MAGENTO_NAME;
		}
		elseif($typeID === self::EMAIL)
		{
			return self::EMAIL_NAME;
		}
		elseif($typeID === self::VOXIMPLANT)
		{
			return self::VOXIMPLANT_NAME;
		}
		elseif($typeID === self::IMOPENLINE)
		{
			return self::IMOPENLINE_NAME;
		}
		elseif($typeID === self::WEBFORM)
		{
			return self::WEBFORM_NAME;
		}
		elseif($typeID === self::SITEBUTTON)
		{
			return self::SITEBUTTON_NAME;
		}
		elseif($typeID === self::LEAD_IMPORT)
		{
			return self::LEAD_IMPORT_NAME;
		}
		return '';
	}
	/**
	 * @param string $typeName Type Name.
	 * @return int
	 */
	public static function resolveID($typeName)
	{
		if(!is_string($typeName))
		{
			return self::UNDEFINED;
		}

		$typeName = strtoupper($typeName);
		if($typeName === self::EXTERNAL_CUSTOM_NAME)
		{
			return self::EXTERNAL_CUSTOM;
		}
		if($typeName === self::EXTERNAL_BITRIX_NAME)
		{
			return self::EXTERNAL_BITRIX;
		}
		if($typeName === self::EXTERNAL_ONE_C_NAME)
		{
			return self::EXTERNAL_ONE_C;
		}
		if($typeName === self::EXTERNAL_WORDPRESS_NAME)
		{
			return self::EXTERNAL_WORDPRESS;
		}
		if($typeName === self::EXTERNAL_JOOMLA_NAME)
		{
			return self::EXTERNAL_JOOMLA;
		}
		if($typeName === self::EXTERNAL_DRUPAL_NAME)
		{
			return self::EXTERNAL_DRUPAL;
		}
		elseif($typeName === self::EMAIL_NAME)
		{
			return self::EMAIL;
		}
		elseif($typeName === self::VOXIMPLANT_NAME)
		{
			return self::VOXIMPLANT;
		}
		elseif($typeName === self::IMOPENLINE_NAME)
		{
			return self::IMOPENLINE;
		}
		elseif($typeName === self::WEBFORM_NAME)
		{
			return self::WEBFORM;
		}
		elseif($typeName === self::SITEBUTTON_NAME)
		{
			return self::SITEBUTTON;
		}
		elseif($typeName === self::LEAD_IMPORT_NAME)
		{
			return self::LEAD_IMPORT;
		}
		return self::UNDEFINED;
	}
}
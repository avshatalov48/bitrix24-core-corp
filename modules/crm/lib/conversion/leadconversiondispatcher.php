<?php
namespace Bitrix\Crm\Conversion;

use Bitrix\Crm;
use Bitrix\Crm\CustomerType;

class LeadConversionDispatcher
{
	/** @var array */
	private static $configs = array();

	public static function resolveTypeID(array $fields)
	{
		return LeadConversionType::resolveByEntityFields($fields);
	}

	public static function getAllConfigurations()
	{
		return array(
			LeadConversionType::GENERAL =>
				self::getConfiguration(array('TYPE_ID' => LeadConversionType::GENERAL)),
			LeadConversionType::RETURNING_CUSTOMER =>
				self::getConfiguration(array('TYPE_ID' => LeadConversionType::RETURNING_CUSTOMER)),
			LeadConversionType::SUPPLEMENT =>
				self::getConfiguration(array('TYPE_ID' => LeadConversionType::SUPPLEMENT))
		);
	}

	public static function getJavaScriptConfigurations()
	{
		$results = array();
		foreach (self::getAllConfigurations() as $typeID => $configuration)
		{
			$results[$typeID] = $configuration->toJavaScript();
		}
		return $results;
	}

	/**
	 * @param array $params
	 * @return LeadConversionConfig
	 */
	public static function getConfiguration(array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$typeID = isset($params['TYPE_ID']) ? (int)$params['TYPE_ID'] : LeadConversionType::UNDEFINED;
		if($typeID === LeadConversionType::UNDEFINED)
		{
			$ID = isset($params['ID']) ? (int)$params['ID'] : 0;
			$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;

			if(!is_array($fields))
			{
				if($ID > 0)
				{
					$dbResult = \CCrmLead::GetListEx(
						array(),
						array(
							'ID' => $ID,
							'CHECK_PERMISSIONS' => 'N',
							false,
							false,
							array('ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER')
						)
					);
					$fields = $dbResult->Fetch();
				}
				if(!is_array($fields))
				{
					$fields = array();
				}
			}

			$typeID = self::resolveTypeID($fields);
		}

		if(!isset(self::$configs[$typeID]))
		{
			$config = LeadConversionConfig::load(array('TYPE_ID' => $typeID));
			if($config === null)
			{
				$config = LeadConversionConfig::getDefault(array('TYPE_ID' => $typeID));
			}
			self::$configs[$typeID] = $config;
		}
		return self::$configs[$typeID];
	}
}
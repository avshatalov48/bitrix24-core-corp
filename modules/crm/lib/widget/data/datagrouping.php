<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

class DataGrouping
{
	const UNDEFINED = '';
	const DATE = 'DATE';
	const USER = 'USER';
	private static $messagesLoaded = false;
	private static $descriptions = null;

	public static function isDefined($contextID)
	{
		if(!is_string($contextID))
		{
			return false;
		}

		$contextID = strtoupper($contextID);
		return $contextID === self::DATE || $contextID === self::USER;
	}

	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions = array(
				self::UNDEFINED => '',
				self::DATE => GetMessage('CRM_DATA_GROUP_DATE'),
				self::USER => GetMessage('CRM_DATA_GROUP_USER')
			);
		}
		return self::$descriptions;
	}
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}
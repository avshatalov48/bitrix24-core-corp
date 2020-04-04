<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

class DataContext
{
	const UNDEFINED = '';
	const ENTITY = 'E';
	const FUND = 'F';
	const PERCENT = 'P';

	private static $messagesLoaded = false;
	private static $descriptions = null;

	public static function isDefined($contextID)
	{
		if(!is_string($contextID))
		{
			return false;
		}

		$contextID = strtoupper($contextID);
		return $contextID === self::ENTITY || $contextID === self::FUND || $contextID === self::PERCENT;
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
				self::ENTITY => GetMessage('CRM_DATA_CONTEXT_ENTITY'),
				self::FUND => GetMessage('CRM_DATA_CONTEXT_FUND'),
				self::PERCENT => GetMessage('CRM_DATA_CONTEXT_PERCENT')
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
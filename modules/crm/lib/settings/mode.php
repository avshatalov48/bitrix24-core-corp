<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Main;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Mode
 * CRM Operating Mode
 * @package Bitrix\Crm\Settings
 */
class Mode
{
	const Undefined = 0;
	//Leads are enabled.
	const CLASSIC = 1;
	//Leads are disabled.
	const SIMPLE = 2;

	/** @var array|null */
	protected static $descriptions = null;

	/**
	 * Get Current CRM Mode
	 * @return int
	 */
	public static function getCurrent()
	{
		return Crm\Settings\LeadSettings::isEnabled() ? self::CLASSIC : self::SIMPLE;
	}

	/**
	 * Get Mode Descriptions
	 * @return array
	 */
	public static function getAllDescriptions()
	{
		if(!self::$descriptions)
		{
			self::$descriptions = array(
				self::CLASSIC => Main\Localization\Loc::getMessage('CRM_MODE_CLASSIC'),
				self::SIMPLE => GetMessage('CRM_MODE_SIMPLE'),
			);
		}
		return self::$descriptions;
	}
}
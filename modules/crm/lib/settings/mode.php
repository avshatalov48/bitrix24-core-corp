<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Crm;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Mode
 * CRM Operating Mode
 * @package Bitrix\Crm\Settings
 */
class Mode
{
	public const Undefined = 0;
	//Leads are enabled.
	public const CLASSIC = 1;
	//Leads are disabled.
	public const SIMPLE = 2;

	public const CLASSIC_NAME = 'CLASSIC';
	public const SIMPLE_NAME = 'SIMPLE';

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

	public static function getCurrentName(): string
	{
		return match (self::getCurrent()) {
			self::CLASSIC => self::CLASSIC_NAME,
			self::SIMPLE => self::SIMPLE_NAME,
		};
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

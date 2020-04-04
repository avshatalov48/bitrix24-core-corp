<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;

class FilterPeriodType
{
	const UNDEFINED = '';
	const YEAR = 'Y';
	const QUARTER = 'Q';
	const MONTH = 'M';
	const CURRENT_MONTH = 'M0';
	const CURRENT_QUARTER = 'Q0';
	const CURRENT_DAY = 'D0';
	const LAST_DAYS_90 = 'D90';
	const LAST_DAYS_60 = 'D60';
	const LAST_DAYS_30 = 'D30';
	const LAST_DAYS_7 = 'D7';
	const BEFORE = 'B';

	private static $messagesLoaded = false;
	/**
	* @return boolean
	*/
	public static function isDefined($typeID)
	{
		return $typeID === self::YEAR
			|| $typeID === self::QUARTER
			|| $typeID === self::MONTH
			|| $typeID === self::CURRENT_MONTH
			|| $typeID === self::CURRENT_QUARTER
			|| $typeID === self::CURRENT_DAY
			|| $typeID === self::LAST_DAYS_90
			|| $typeID === self::LAST_DAYS_60
			|| $typeID === self::LAST_DAYS_30
			|| $typeID === self::LAST_DAYS_7
			|| $typeID === self::BEFORE;
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		self::includeModuleFile();
		return array(
			self::UNDEFINED  => GetMessage('CRM_FILTER_PERIOD_TYPE_UNDEFINED'),
			self::YEAR => GetMessage('CRM_FILTER_PERIOD_TYPE_YEAR'),
			self::QUARTER => GetMessage('CRM_FILTER_PERIOD_TYPE_QUARTER'),
			self::MONTH => GetMessage('CRM_FILTER_PERIOD_TYPE_MONTH'),
			self::CURRENT_MONTH => GetMessage('CRM_FILTER_PERIOD_TYPE_CURRENT_MONTH'),
			self::CURRENT_QUARTER => GetMessage('CRM_FILTER_PERIOD_TYPE_CURRENT_QUARTER'),
			self::CURRENT_DAY => GetMessage('CRM_FILTER_PERIOD_TYPE_CURRENT_DAY'),
			self::LAST_DAYS_90 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_90'),
			self::LAST_DAYS_60 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_60'),
			self::LAST_DAYS_30 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_30'),
			self::LAST_DAYS_7 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_7'),
			self::BEFORE => GetMessage('CRM_FILTER_PERIOD_TYPE_BEFORE')
		);
	}
	/**
	* @return string
	*/
	public static function getDescription($typeID)
	{
		$descriptions = self::getAllDescriptions();
		return isset($descriptions[$typeID]) ? $descriptions[$typeID] : '';
	}
	/**
	 * Convert System Date Type to Widget Period.
	 * @param string $dateType Source Date Type (please see Bitrix\Main\UI\Filter\DateType).
	 * @return string
	 */
	public static function convertFromDateType($dateType)
	{
		$result = self::UNDEFINED;
		if($dateType === Main\UI\Filter\DateType::YEAR)
		{
			$result = self::YEAR;
		}
		else if($dateType === Main\UI\Filter\DateType::QUARTER)
		{
			$result = self::QUARTER;
		}
		else if($dateType === Main\UI\Filter\DateType::CURRENT_QUARTER)
		{
			$result = self::CURRENT_QUARTER;
		}
		else if($dateType === Main\UI\Filter\DateType::MONTH)
		{
			$result = self::MONTH;
		}
		else if($dateType === Main\UI\Filter\DateType::CURRENT_MONTH)
		{
			$result = self::CURRENT_MONTH;
		}
		else if($dateType === Main\UI\Filter\DateType::LAST_7_DAYS)
		{
			$result = self::LAST_DAYS_7;
		}
		else if($dateType === Main\UI\Filter\DateType::LAST_30_DAYS)
		{
			$result = self::LAST_DAYS_30;
		}
		else if($dateType === Main\UI\Filter\DateType::LAST_60_DAYS)
		{
			$result = self::LAST_DAYS_60;
		}
		else if($dateType === Main\UI\Filter\DateType::LAST_90_DAYS)
		{
			$result = self::LAST_DAYS_90;
		}
		return $result;
	}
	/**
	 * Convert Widget Filter Period to System Date Type.
	 * @param string $period Source Widget Filter Period.
	 * @return string
	 */
	public static function convertToDateType($period)
	{
		$result = '';
		if($period === self::YEAR)
		{
			$result = Main\UI\Filter\DateType::YEAR;
		}
		else if($period === self::QUARTER)
		{
			$result = Main\UI\Filter\DateType::QUARTER;
		}
		else if($period === self::CURRENT_QUARTER)
		{
			$result = Main\UI\Filter\DateType::CURRENT_QUARTER;
		}
		else if($period === self::MONTH)
		{
			$result = Main\UI\Filter\DateType::MONTH;
		}
		else if($period === self::CURRENT_MONTH)
		{
			$result = Main\UI\Filter\DateType::CURRENT_MONTH;
		}
		else if($period === self::LAST_DAYS_7)
		{
			$result = Main\UI\Filter\DateType::LAST_7_DAYS;
		}
		else if($period === self::LAST_DAYS_30)
		{
			$result = Main\UI\Filter\DateType::LAST_30_DAYS;
		}
		else if($period === self::LAST_DAYS_60)
		{
			$result = Main\UI\Filter\DateType::LAST_60_DAYS;
		}
		else if($period === self::LAST_DAYS_90)
		{
			$result = Main\UI\Filter\DateType::LAST_90_DAYS;
		}
		return $result;
	}
	/**
	* @return void
	*/
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
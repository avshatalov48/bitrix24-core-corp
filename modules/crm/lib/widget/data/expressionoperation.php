<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

class ExpressionOperation
{
	const SUM_OPERATION = 'SUM';
	const DIFF_OPERATION = 'DIFF';
	const PERCENT_OPERATION = 'PC';
	private static $messagesLoaded = false;
	private static $descriptions = null;
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions = array(
				self::SUM_OPERATION => GetMessage('CRM_DATA_EXPR_SUM'),
				self::DIFF_OPERATION => GetMessage('CRM_DATA_EXPR_DIFF'),
				self::PERCENT_OPERATION => GetMessage('CRM_DATA_EXPR_PC')
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
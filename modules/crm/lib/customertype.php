<?php
namespace Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

class CustomerType
{
	const UNDEFINED = 0;
	const GENERAL = 1;
	const RETURNING = 2;

	const GENERAL_NAME = 'GENERAL';
	const RETURNING_NAME = 'RETURNING';

	private static $ALL_DESCRIPTIONS = null;

	/**
	 *  Try to resolve type name by ID.
	 * @param int $ID Type ID.
	 * @return string
	 */
	public static function resolveName($ID)
	{
		if (!is_numeric($ID))
		{
			return '';
		}

		$ID = (int)$ID;
		if ($ID <= 0)
		{
			return '';
		}

		if($ID === self::GENERAL)
		{
			return self::GENERAL_NAME;
		}
		else if($ID === self::RETURNING)
		{
			return self::RETURNING_NAME;
		}
		return '';
	}

	public static function getAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS[LANGUAGE_ID])
		{
			Loc::loadCustomMessages(__FILE__);
			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
				self::GENERAL => GetMessage('CRM_CUSTOMER_GENERAL'),
				self::RETURNING => GetMessage('CRM_CUSTOMER_RETURNING')
			);
		}
		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}

	public static function getDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::getAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}
}
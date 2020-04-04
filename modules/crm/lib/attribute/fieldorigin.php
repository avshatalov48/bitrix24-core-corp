<?php
namespace Bitrix\Crm\Attribute;

class FieldOrigin
{
	const UNDEFINED = 0;
	const SYSTEM    = 1;
	const CUSTOM    = 2;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::SYSTEM && $typeID <= self::CUSTOM;
	}
}

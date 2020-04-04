<?php
namespace Bitrix\Crm\Attribute;

class FieldAttributeType
{
	const UNDEFINED = 0;
	const HIDDEN    = 1;
	const READONLY  = 2;
	const REQUIRED  = 3;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::HIDDEN && $typeID <= self::REQUIRED;
	}
}

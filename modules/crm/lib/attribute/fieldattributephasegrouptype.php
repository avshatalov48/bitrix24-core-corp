<?php
namespace Bitrix\Crm\Attribute;

class FieldAttributePhaseGroupType
{
	const UNDEFINED = 0;
	const ALL       = 1;
	const PIPELINE  = 2;
	const JUNK      = 3;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::ALL && $typeID <= self::JUNK;
	}
}
<?php

namespace Bitrix\Crm\History;

class HistoryEntryType
{
	public const UNDEFINED = 0;
	public const CREATION = 1;
	public const MODIFICATION = 2;
	public const FINALIZATION = 3;
	public const JUNK = 4;
	public const CATEGORY_CHANGE = 5;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return $typeID >= self::CREATION && $typeID <= self::CATEGORY_CHANGE;
	}
}

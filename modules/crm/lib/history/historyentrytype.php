<?php
namespace Bitrix\Crm\History;
use Bitrix\Main;
class HistoryEntryType
{
	const UNDEFINED = 0;
	const CREATION = 1;
	const MODIFICATION = 2;
	const FINALIZATION = 3;
	const JUNK = 4;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return $typeID >= self::CREATION && $typeID <= self::JUNK;
	}
}
<?
IncludeModuleLangFile(__FILE__);

class CAllIntranetSharepointLog
{
	protected static $log = null;
	protected static $log_min_id = 0;


	public static function Next($IBLOCK_ID = 0, $cnt = 0)
	{
		global $DB;

		if (self::$log === null)
		{
			self::$log = array();
			
			$IBLOCK_ID = intval($IBLOCK_ID);
			
			$cnt = intval($cnt);
			if ($cnt <= 0) 
				$cnt = BX_INTRANET_SP_LOG_COUNT_MANUAL;
		
			$strWhere = "WHERE ISPL.ID>'".self::$log_min_id."'";
			$strWhere .= $IBLOCK_ID > 0 ? " AND ISPL.IBLOCK_ID='".$IBLOCK_ID."'" : '';
		
			if (!($query = CIntranetSharepointLog::_LimitQuery($strWhere, $cnt)))
				return false;

			$dbRes = $DB->Query($query, false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
			
			while ($arRes = $dbRes->Fetch())
			{
				array_push(self::$log, $arRes);
			}
		}
		
		$res = array_shift(self::$log);

		return $res;
	}


	public static function Add($arFields)
	{
		$dbRes = CIBlockElement::GetProperty($arFields['IBLOCK_ID'], $arFields['ID'], array(), array('CODE' => 'OWSHIDDENVERSION'));

		if ($arRes = $dbRes->Fetch())
		{
			return $GLOBALS['DB']->Insert('b_intranet_sharepoint_log', array(
				'IBLOCK_ID' => "'".intval($arFields['IBLOCK_ID'])."'",
				'ELEMENT_ID' => "'".intval($arFields['ID'])."'",
				'VERSION' => "'".intval($arRes['VALUE'])."'",
			), "", false, "", true);
		}
	}

	public static function ItemUpdated($IBLOCK_ID, $ID)
	{
		$dbRes = $GLOBALS['DB']->Query('SELECT VERSION FROM b_intranet_sharepoint_log WHERE IBLOCK_ID='.intval($IBLOCK_ID).' AND ELEMENT_ID='.intval($ID), false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes['VERSION'];

		return false;
	}

	public static function ItemUpdatedClear($IBLOCK_ID, $ID)
	{
		return $GLOBALS['DB']->Query('DELETE FROM b_intranet_sharepoint_log WHERE IBLOCK_ID='.intval($IBLOCK_ID).' AND ELEMENT_ID='.intval($ID), false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
	}

	public static function Clear($arIDs)
	{
		$arKeys = array();
		foreach ($arIDs as $key) $arKeys[] = "'".intval($key)."'";

		return $GLOBALS['DB']->Query('
DELETE FROM b_intranet_sharepoint_log
WHERE ID IN ('.implode(', ', $arKeys).')
', false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
	}

	public static function IsLog($IBLOCK_ID = 0)
	{
		global $DB;

		$strWhere = $IBLOCK_ID > 0 ? 'WHERE ISPL.IBLOCK_ID='.intval($IBLOCK_ID) : '';
		$query = CIntranetSharepointLog::_LimitQuery($strWhere, 1);
		$dbRes = $DB->Query($query, false, "FILE: ".__FILE__."<br> LINE:".__LINE__);

		return ($dbRes->Fetch() ? true : false);
	}
}
?>
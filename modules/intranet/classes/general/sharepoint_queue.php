<?
IncludeModuleLangFile(__FILE__);

class CAllIntranetSharepointQueue
{
	protected static $queue = array();
	protected static $queue_min_id = 0;

	protected static function CheckFields($action, &$arFields)
	{
		global $APPLICATION;

		if ($arFields['IBLOCK_ID'] = intval($arFields['IBLOCK_ID']))
		{
			$dbRes = CIntranetSharepoint::GetByID($arFields['IBLOCK_ID']);
			if (!$arRes = $dbRes->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('ISPQ_ERROR_SERVICE_NOT_REGISTERED', array('ID' => $arFields['IBLOCK_ID'])));
				return false;
			}
		}

		if (strlen($arFields['SP_METHOD']) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage('ISPQ_ERROR_NO_METHOD'));
			return false;
		}

		$arFields['SP_METHOD'] = trim($arFields['SP_METHOD']);

		return true;
	}

	public static function Next($IBLOCK_ID = 0, $cnt = 0)
	{
		global $DB;

		if (count(self::$queue) <= 0)
		{
			$IBLOCK_ID = intval($IBLOCK_ID);

			$cnt = intval($cnt);
			if ($cnt <= 0)
				$cnt = BX_INTRANET_SP_QUEUE_COUNT;

			$strWhere = "WHERE ISPQ.ID>'".self::$queue_min_id."'";
			$strWhere .= $IBLOCK_ID > 0 ? " AND ISPQ.IBLOCK_ID='".$IBLOCK_ID."'" : '';

			if (!($query = CIntranetSharepointQueue::_LimitQuery($strWhere, $cnt)))
				return false;

			$dbRes = $DB->Query($query, false, "FILE: ".__FILE__."<br> LINE:".__LINE__);

			while ($arRes = $dbRes->Fetch())
			{
				array_push(self::$queue, $arRes);
			}
		}

		$res = array_shift(self::$queue);

		if (is_array($res))
		{
			if (strlen($res['SP_METHOD_PARAMS']) > 0)
				$res['SP_METHOD_PARAMS'] = unserialize($res['SP_METHOD_PARAMS']);

			if (strlen($res['CALLBACK']) > 0)
				$res['CALLBACK'] = unserialize($res['CALLBACK']);
		}

		return $res;
	}

	public static function Add($arFields)
	{
		global $DB;

		if (!self::CheckFields('ADD', $arFields))
			return false;

		if (is_array($arFields['SP_METHOD_PARAMS']) > 0)
			$arFields['SP_METHOD_PARAMS'] = serialize($arFields['SP_METHOD_PARAMS']);

		if (is_array($arFields['CALLBACK']) > 0)
			$arFields['CALLBACK'] = serialize($arFields['CALLBACK']);

		$arInsert = array(
			'IBLOCK_ID' => "'".$arFields['IBLOCK_ID']."'",
			'SP_METHOD' => "'".$DB->ForSql($arFields['SP_METHOD'])."'",
			'SP_METHOD_PARAMS' => "'".$DB->ForSql($arFields['SP_METHOD_PARAMS'])."'",
			'CALLBACK' => "'".$DB->ForSql($arFields['CALLBACK'])."'",
		);

		return $DB->Insert('b_intranet_sharepoint_queue', $arInsert);
	}

	public static function Delete($ID)
	{
		return $GLOBALS['DB']->Query("DELETE FROM b_intranet_sharepoint_queue WHERE ID='".intval($ID)."'", false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
	}

	public static function Clear($IBLOCK_ID = false)
	{
		if (self::$queue_min_id > 0)
		{
			$IBLOCK_ID = intval($IBLOCK_ID);

			$query = "DELETE FROM b_intranet_sharepoint_queue WHERE ID<='".intval(self::$queue_min_id)."'";
			if ($IBLOCK_ID)
				$query .= ' AND IBLOCK_ID='.intval($IBLOCK_ID);

			$GLOBALS['DB']->Query($query, false, "FILE: ".__FILE__."<br> LINE:".__LINE__);
			self::SetMinID(0);

			return true;
		}
		else
			return false;
	}

	public static function SetMinID($ID)
	{
		self::$queue_min_id = intval($ID);
	}

	public static function Lock()
	{
		$lock_path = CTempFile::GetAbsoluteRoot().'/';
		$lock_filename = $lock_path.'sharepoint.lock.txt';
		$lock_ts = time();

		if (file_exists($lock_filename))
		{
			$ts = file_get_contents($lock_filename);

			if ($lock_ts - $ts < 300)
				return false;
		}

		CheckDirPath($lock_path);

		$fp = fopen($lock_filename, 'w');
		fwrite($fp, $lock_ts);
		fclose($fp);

		return true;
	}

	public static function Unlock()
	{
		$lock_filename = CTempFile::GetAbsoluteRoot().'/sharepoint.lock.txt';
		if (file_exists($lock_filename))
			@unlink($lock_filename);

		return true;
	}

	public static function IsQueue($IBLOCK_ID = false)
	{
		global $DB;

		$strWhere = $IBLOCK_ID > 0 ? 'WHERE ISPQ.IBLOCK_ID='.intval($IBLOCK_ID) : '';
		$query = CIntranetSharepointQueue::_LimitQuery($strWhere, 1);

		$dbRes = $DB->Query($query, false, "FILE: ".__FILE__."<br> LINE:".__LINE__);

		return ($dbRes->Fetch() ? true : false);
	}
}
?>
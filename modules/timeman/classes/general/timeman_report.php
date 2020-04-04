<?
abstract class CAllTimeManReport
{
	public static function CheckFields($action, &$arFields)
	{
		global $DB, $USER;

		if ($action == 'ADD')
		{
			if (!$arFields['ENTRY_ID'])
				return false;

			if (!$arFields['USER_ID'])
				$arFields['USER_ID'] = $USER->GetID();
		}

		if (isset($arFields['REPORT']))
			$arFields['REPORT'] = trim($arFields['REPORT']);

		if (isset($arFields['ACTIVE']))
			$arFields['ACTIVE'] = $arFields['ACTIVE'] == 'N' ? 'N' : 'Y';

		unset($arFields['TIMESTAMP_X']);

		if ($action == 'UPDATE')
			$arFields['~TIMESTAMP_X'] = $DB->GetNowFunction();

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		$e = GetModuleEvents('timeman', 'OnBeforeTMReportAdd');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array($arFields)))
				return false;
		}

		if (!self::CheckFields('ADD', $arFields))
			return false;

		if ($ID = $DB->Add('b_timeman_reports', $arFields, array('REPORT')))
		{
			$arFields['ID'] = $ID;

			$e = GetModuleEvents('timeman', 'OnAfterTMReportAdd');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($arFields));
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$e = GetModuleEvents('timeman', 'OnBeforeTMReportUpdate');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array($arFields)))
				return false;
		}

		if (!self::CheckFields('UPDATE', $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate('b_timeman_reports', $arFields);

		$query = 'UPDATE b_timeman_reports SET '.$strUpdate.' WHERE ID=\''.intval($ID).'\'';

		$arBinds = array();
		if(isset($arFields['REPORT']))
		{
			$arBinds['REPORT'] = $arFields['REPORT'];
		}

		if (($dbRes = $DB->QueryBind($query, $arBinds)) && ($dbRes->AffectedRowsCount() > 0))
		{
			$e = GetModuleEvents('timeman', 'OnAfterTMReportUpdate');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($ID, $arFields));

			return $ID;
		}

		return false;
	}

	public static function GetByID($ID)
	{
		return self::GetList(array(), array('ID' => $ID));
	}

	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "R.ID", "TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD" => "R.TIMESTAMP_X", "TYPE" => "datetime"),
			"ENTRY_ID" => array("FIELD" => "R.ENTRY_ID", "TYPE" => "int"),
			"USER_ID" => array("FIELD" => "R.USER_ID", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "R.ACTIVE", "TYPE" => "string"),
			"REPORT_TYPE" => array("FIELD" => "R.REPORT_TYPE", "TYPE" => "string"),
			"REPORT" => array("FIELD" => "R.REPORT", "TYPE" => "string"),
		);

		$strSql = '
SELECT R.*, '.$DB->DateToCharFunction("R.TIMESTAMP_X", "FULL").' AS TIMESTAMP_X
FROM b_timeman_reports R
WHERE 1=1';

		foreach ($arFilter as $fld => $val)
		{
			$fld = ToUpper($fld);

			if ($arFields[$fld])
			{
				if ($arFields[$fld]['TYPE'] == 'int')
					$val = intval($val);
				else
					$val = $DB->ForSQL($val);

				$strSql .= ' AND ('.$arFields[$fld]['FIELD'].'=\''.$val.'\')';
			}
		}

		$strOrder = '';
		foreach ($arOrder as $fld => $dir)
		{
			$fld = ToUpper($fld);

			if ($arFields[$fld])
			{
				$strOrder .= ($strOrder == '' ? '' : ', ')
					.$arFields[$fld]['FIELD'].' '.(ToUpper($dir) == 'DESC' ? 'DESC' : 'ASC');
			}
		}

		if (strlen($strOrder) > 0)
		{
			$strSql .= ' ORDER BY '.$strOrder;
		}

		return ($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}

	public static function Reopen($ENTRY_ID)
	{
		global $DB;

		$query = 'DELETE FROM b_timeman_reports WHERE ENTRY_ID=\''.intval($ENTRY_ID).'\' AND (REPORT_TYPE=\'ERR_CLOSE\')';

		return $DB->Query($query);
	}

	public static function Approve($ENTRY_ID)
	{
		global $DB, $USER;

		$query = 'UPDATE b_timeman_reports SET TIMESTAMP_X='.$DB->GetNowFunction().', ACTIVE=\'N\', USER_ID=\''.$USER->GetID().'\' WHERE ENTRY_ID=\''.intval($ENTRY_ID).'\' AND (REPORT_TYPE=\'ERR_OPEN\' OR REPORT_TYPE=\'ERR_CLOSE\' OR REPORT_TYPE=\'ERR_DURATION\')';

		return $DB->Query($query);
	}
}
?>
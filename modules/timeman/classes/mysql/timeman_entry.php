<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/timeman/classes/general/timeman_entry.php");

class CTimeManEntry extends CAllTimeManEntry
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("USER", "E.USER_ID");
		$obUserFieldsSql->SetSelect($arSelectFields);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$join_user = "LEFT JOIN b_user U ON (E.USER_ID = U.ID)";
		$arFields = array(
			"ID" => array("FIELD" => "E.ID", "TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD" => "E.TIMESTAMP_X", "TYPE" => "datetime"),
			"USER_ID" => array("FIELD" => "E.USER_ID", "TYPE" => "int"),
			"MODIFIED_BY" => array("FIELD" => "E.MODIFIED_BY", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "E.ACTIVE", "TYPE" => "char"),
			"PAUSED" => array("FIELD" => "E.PAUSED", "TYPE" => "char"),
			"DATE_START" => array("FIELD" => "E.DATE_START", "TYPE" => "datetime"),
			"DATE_FINISH" => array("FIELD" => "E.DATE_FINISH", "TYPE" => "datetime"),
			"TIME_START" => array("FIELD" => "E.TIME_START", "TYPE" => "int"),
			"TIME_FINISH" => array("FIELD" => "E.TIME_FINISH", "TYPE" => "int"),
			"DURATION" => array("FIELD" => "E.DURATION", "TYPE" => "int"),
			"TIME_LEAKS" => array("FIELD" => "E.TIME_LEAKS", "TYPE" => "int"),
			"TASKS" => array("FIELD" => "E.TASKS", "TYPE" => "string"),
			"IP_OPEN" => array("FIELD" => "E.IP_OPEN", "TYPE" => "string"),
			"IP_CLOSE" => array("FIELD" => "E.IP_CLOSE", "TYPE" => "string"),
			"FORUM_TOPIC_ID" => array("FIELD" => "E.FORUM_TOPIC_ID", "TYPE" => "int"),
			"LAT_OPEN" => array("FIELD" => "E.LAT_OPEN", "TYPE" => "double"),
			"LON_OPEN" => array("FIELD" => "E.LON_OPEN", "TYPE" => "double"),
			"LAT_CLOSE" => array("FIELD" => "E.LAT_CLOSE", "TYPE" => "double"),
			"LON_CLOSE" => array("FIELD" => "E.LON_CLOSE", "TYPE" => "double"),

			"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => $join_user),
			"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_SECOND_NAME" => array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => $join_user),
			"USER_GENDER" => array("FIELD" => "U.PERSONAL_GENDER", "TYPE" => "string", "FROM" => $join_user),
			"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => $join_user),

			"ACTIVATED" => array("FIELD" => "CASE WHEN E.ACTIVE='Y' AND EXISTS(
					SELECT
						'x'
					FROM
						b_timeman_reports TR
					WHERE
						TR.ENTRY_ID = E.ID
						AND TR.ACTIVE = 'N'
				) THEN 'Y' ELSE 'N' END", "TYPE" => "string"),
			"INACTIVE_OR_ACTIVATED" => array("FIELD" => "CASE WHEN E.ACTIVE='N' OR EXISTS(
					SELECT
						'x'
					FROM
						b_timeman_reports TR
					WHERE
						TR.ENTRY_ID = E.ID
						AND TR.ACTIVE = 'N'
				) THEN 'Y' ELSE 'N' END", "TYPE" => "string"),
		);

		if (count($arSelectFields) <= 0)
		{
			foreach ($arFields as $k => $v)
			{
				if (!isset($v['FROM']) && $k != 'ACTIVATED' && $k != 'INACTIVE_OR_ACTIVATED')
					$arSelectFields[] = $k;
			}
		}
		elseif(in_array("*", $arSelectFields))
		{
			$arf = $arFields;
			if (!in_array('ACTIVATED', $arSelectFields))
				unset($arf['ACTIVATED']);
			if (!in_array('INACTIVE_OR_ACTIVATED', $arSelectFields))
				unset($arf['INACTIVE_OR_ACTIVATED']);
			$arSelectFields = array_keys($arf);
		}

		$arSqls = CTimeManEntry::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql);

		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r)>0)
			$strSqlUFFilter = " (".$r.") ";

		if ($obUserFieldsSql->GetDistinct())
		{
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", " DISTINCT ", $arSqls["SELECT"]);
		}
		else
		{
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
		}

		$strSql = "
SELECT
	".$arSqls["SELECT"]." "."
	".$obUserFieldsSql->GetSelect()." "."
FROM
	b_timeman_entries E
	".$arSqls["FROM"]." "."
	".$obUserFieldsSql->GetJoin("E.USER_ID")." "."
";

		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		if (strlen($strSqlUFFilter) > 0)
		{
			$strSql .= (strlen($arSqls["WHERE"]) > 0) ? ' AND ' : ' WHERE ';
			$strSql .= $strSqlUFFilter;
		}

		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp = "
SELECT COUNT('x') as CNT
FROM
	b_timeman_entries E
	".$arSqls["FROM"]."
	".$obUserFieldsSql->GetJoin("E.USER_ID")."
";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";

			if (strlen($strSqlUFFilter) > 0)
			{
				$strSql_tmp .= (strlen($arSqls["WHERE"]) > 0) ? ' AND ' : ' WHERE ';
				$strSql_tmp .= $strSqlUFFilter;
			}

			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("USER"));
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".$arNavStartParams["nTopCount"];
//echo '<pre>',$strSql,'</pre>'; die();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("USER"));
		}

		return $dbRes;
	}

	protected static function _GetLastQuery($USER_ID)
	{
		global $DB;

		return '
SELECT
	E.*,
	'.$DB->DateToCharFunction("TIMESTAMP_X", "FULL").' TIMESTAMP_X,
	'.$DB->DateToCharFunction("DATE_START", "FULL").' DATE_START,
	'.$DB->DateToCharFunction("DATE_FINISH", "FULL").' DATE_FINISH
FROM b_timeman_entries E
WHERE USER_ID=\''.intval($USER_ID).'\'
ORDER BY E.DATE_START DESC
LIMIT 0,1
';
	}

}
?>
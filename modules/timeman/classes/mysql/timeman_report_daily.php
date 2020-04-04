<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/timeman/classes/general/timeman_report_daily.php");

class CTimeManReportDaily extends CAllTimeManReportDaily
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$TABLE = 'b_timeman_report_daily R';

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("USER", "R.USER_ID");
		$obUserFieldsSql->SetSelect($arSelectFields);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$join_user = "LEFT JOIN b_user U ON (R.USER_ID = U.ID)";
		$arFields = array(
			"ID" => array("FIELD" => "R.ID", "TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD" => "R.TIMESTAMP_X", "TYPE" => "datetime"),
			"ACTIVE" => array("FIELD" => "R.ACTIVE", "TYPE" => "string"),
			"USER_ID" => array("FIELD" => "R.USER_ID", "TYPE" => "int"),
			"ENTRY_ID" => array("FIELD" => "R.ENTRY_ID", "TYPE" => "int"),
			"REPORT_DATE" => array("FIELD" => "R.REPORT_DATE", "TYPE" => "date"),
			"TASKS" => array("FIELD" => "R.TASKS", "TYPE" => "string"),
			"EVENTS" => array("FIELD" => "R.EVENTS", "TYPE" => "string"),
			"REPORT" => array("FIELD" => "R.REPORT", "TYPE" => "string"),
			"MARK" => array("FIELD" => "R.MARK", "TYPE" => "int"),

			"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => $join_user),
			"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_SECOND_NAME" => array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => $join_user),
			"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => $join_user),
			"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => $join_user),
		);

		if(in_array("*", $arSelectFields))
		{
			$arSelectFields = array_keys($arFields);
		}
		elseif (count($arSelectFields) <= 0)
		{
			foreach ($arFields as $key => $fld)
			{
				if (!$fld['FROM'])
					$arSelectFields[] = $key;
			}
		}

		$arSqls = CTimeManReportDaily::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql);

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
	".$TABLE."
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
			$arRes = $dbRes->Fetch();
			if ($arRes)
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
	".$TABLE."
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
				$arRes = $dbRes->Fetch();
				if ($arRes)
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

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("USER"));
		}

		return $dbRes;
	}
}
?>
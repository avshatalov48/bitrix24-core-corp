<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/meeting/classes/general/meeting.php");

class CMeeting extends CAllMeeting
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$TABLE = 'b_meeting M';
		$TABLE_OWNER = "LEFT JOIN b_meeting_users MU ON MU.MEETING_ID=M.ID AND MU.USER_ROLE='".self::ROLE_OWNER."'";
		$TABLE_USER = "LEFT JOIN b_meeting_users MU1 ON MU1.MEETING_ID=M.ID";
		$TABLE_MEMBER = "LEFT JOIN b_meeting_users MU2 ON MU2.MEETING_ID=M.ID";

		$arFields = array(
			"ID" => array("FIELD" => "M.ID", "TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD" => "M.TIMESTAMP_X", "TYPE" => "datetime"),
			"EVENT_ID" => array("FIELD" => "M.EVENT_ID", "TYPE" => "int"),
			"DATE_START" => array("FIELD" => "M.DATE_START", "TYPE" => "datetime"),
			"DATE_FINISH" => array("FIELD" => "M.DATE_FINISH", "TYPE" => "datetime"),
			"DURATION" => array("FIELD" => "M.DURATION", "TYPE" => "int"),
			"CURRENT_STATE" => array("FIELD" => "M.CURRENT_STATE", "TYPE" => "char"),
			"TITLE" => array("FIELD" => "M.TITLE", "TYPE" => "string"),
			"GROUP_ID" => array("FIELD" => "M.GROUP_ID", "TYPE" => "int"),
			"PARENT_ID" => array("FIELD" => "M.PARENT_ID", "TYPE" => "int"),
			"DESCRIPTION" => array("FIELD" => "M.DESCRIPTION", "TYPE" => "string"),
			"PLACE" => array("FIELD" => "M.PLACE", "TYPE" => "string"),
			"PROTOCOL_TEXT" => array("FIELD" => "M.PROTOCOL_TEXT", "TYPE" => "string"),

			"OWNER_ID" => array("FIELD" => "MU.USER_ID", "TYPE" => "int", "FROM" => $TABLE_OWNER),
			"USER_ID" => array("FIELD" => "MU1.USER_ID", "TYPE" => "int", "FROM" => $TABLE_USER),
			"MEMBER_ID" => array("FIELD" => "MU2.USER_ID", "TYPE" => "int", "FROM" => $TABLE_MEMBER),
			"USER_ROLE" => array("FIELD" => "MU1.USER_ROLE", "TYPE" => "string", "FROM" => $TABLE_USER),
		);

		if (count($arSelectFields) <= 0)
		{
			foreach ($arFields as $k => $v)
			{
				if (!isset($v['FROM']))
					$arSelectFields[] = $k;
			}
		}
		elseif(in_array("*", $arSelectFields))
		{
			if (!array_key_exists('USER_ID', $arFilter))
			{
				$tmpFields = $arFields;
				unset($tmpFields['USER_ROLE']); unset($tmpFields['USER_ID']);

				$arSelectFields = array_keys($tmpFields);
			}
			else
				$arSelectFields = array_keys($arFields);
		}

		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$bNeedDistinct = true; // !
		if($bNeedDistinct)
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);
		else
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "
SELECT
	".$arSqls["SELECT"]." "."
FROM
	".$TABLE."
	".$arSqls["FROM"]." "."
";

		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

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
SELECT COUNT(DISTINCT M.ID) as CNT
FROM
	".$TABLE."
	".$arSqls["FROM"]."
";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";

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
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".$arNavStartParams["nTopCount"];

				
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>
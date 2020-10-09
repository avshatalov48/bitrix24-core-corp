<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/meeting/classes/general/meeting_item_instance.php");

class CMeetingInstance extends CAllMeetingInstance
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$TABLE = 'b_meeting_instance I';

		$arFields = array(
			"ID" => array("FIELD" => "I.ID", "TYPE" => "int"),
			"ITEM_ID" => array("FIELD" => "I.ITEM_ID", "TYPE" => "int"),
			"MEETING_ID" => array("FIELD" => "I.MEETING_ID", "TYPE" => "int"),
			"INSTANCE_PARENT_ID" => array("FIELD" => "I.INSTANCE_PARENT_ID", "TYPE" => "int"),
			"ORIGINAL_TYPE" => array("FIELD" => "I.ORIGINAL_TYPE", "TYPE" => "char"),
			"INSTANCE_TYPE" => array("FIELD" => "I.INSTANCE_TYPE", "TYPE" => "char"),
			"SORT" => array("FIELD" => "I.SORT", "TYPE" => "int"),
			"DURATION" => array("FIELD" => "I.DURATION", "TYPE" => "int"),
			"DEADLINE" => array("FIELD" => "I.DEADLINE", "TYPE" => "datetime"),
			"TASK_ID" => array("FIELD" => "I.TASK_ID", "TYPE" => "int"),
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
			$arSelectFields = array_keys($arFields);
		}

		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "
SELECT
	".$arSqls["SELECT"]." "."
FROM
	".$TABLE."
	".$arSqls["FROM"]." "."
";

		if ($arSqls["WHERE"] <> '')
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		if ($arSqls["GROUPBY"] <> '')
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		if ($arSqls["ORDERBY"] <> '')
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp = "
SELECT COUNT('x') as CNT
FROM
	".$TABLE."
	".$arSqls["FROM"]."
";
			if ($arSqls["WHERE"] <> '')
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";

			if ($arSqls["GROUPBY"] <> '')
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if ($arSqls["GROUPBY"] == '')
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
			if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".$arNavStartParams["nTopCount"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>
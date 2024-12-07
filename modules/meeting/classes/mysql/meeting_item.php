<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/meeting/classes/general/meeting_item.php");

class CMeetingItem extends CAllMeetingItem
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$TABLE = 'b_meeting_item MI';

		$arFields = array(
			"ID" => array("FIELD" => "MI.ID", "TYPE" => "int"),
			"TITLE" => array("FIELD" => "MI.TITLE", "TYPE" => "string"),
			"DESCRIPTION" => array("FIELD" => "MI.DESCRIPTION", "TYPE" => "string"),
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
			$dbRes = $DB->Query($strSql);
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

			$dbRes = $DB->Query($strSql_tmp);
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

			$dbRes = $DB->Query($strSql);
		}

		return $dbRes;
	}
}
?>
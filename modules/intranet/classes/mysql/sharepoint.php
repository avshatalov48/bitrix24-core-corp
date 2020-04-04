<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/classes/general/sharepoint.php');

class CIntranetSharepoint extends CAllIntranetSharepoint
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "I.ID", "TYPE" => "int"),
			"IBLOCK_ID" => array("FIELD" => "S.IBLOCK_ID", "TYPE" => "int"),
			"SP_LIST_ID" => array("FIELD" => "S.SP_LIST_ID", "TYPE" => "string"),
			"SP_URL" => array("FIELD" => "S.SP_URL", "TYPE" => "string"),
			"SP_AUTH_USER" => array("FIELD" => "S.SP_AUTH_USER", "TYPE" => "string"),
			"SP_AUTH_PASS" => array("FIELD" => "S.SP_AUTH_PASS", "TYPE" => "string"),
			"SYNC_DATE" => array("FIELD" => "S.SYNC_DATE", "TYPE" => "datetime"),
			"SYNC_ERRORS" => array("FIELD" => "S.SYNC_ERRORS", "TYPE" => "int"),
			"SYNC_LAST_TOKEN" => array("FIELD" => "S.SYNC_LAST_TOKEN", "TYPE" => "string"),
			"SYNC_PAGING" => array("FIELD" => "S.SYNC_PAGING", "TYPE" => "string"),
			"HANDLER_MODULE" => array("FIELD" => "S.HANDLER_MODULE", "TYPE" => "string"),
			"HANDLER_CLASS" => array("FIELD" => "S.HANDLER_CLASS", "TYPE" => "string"),
			"PRIORITY" => array("FIELD" => "S.PRIORITY", "TYPE" => "string")
		);

		if ($arFilter['SP_LIST_ID']) 
			$arFilter['SP_LIST_ID'] = CIntranetUtils::checkGUID($arFilter['SP_LIST_ID']);
		
		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_intranet_sharepoint S, b_iblock I ".
				"	".$arSqls["FROM"]." ".
				"WHERE S.IBLOCK_ID = I.ID ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "AND ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql = 
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_intranet_sharepoint S, b_iblock I ".
			"	".$arSqls["FROM"]." ".
			"WHERE S.IBLOCK_ID = I.ID ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "AND ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_intranet_sharepoint S, b_iblock I ".
				"	".$arSqls["FROM"]." ".
				"WHERE S.IBLOCK_ID = I.ID ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "AND ".$arSqls["WHERE"]." ";
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
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".$arNavStartParams["nTopCount"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	protected static function _ListNextQuery($limit)
	{
		return '
SELECT IBLOCK_ID 
FROM b_intranet_sharepoint 
WHERE 
	SYNC_PERIOD > 0 
AND SYNC_ERRORS < 3 
AND SYNC_DATE+INTERVAL SYNC_PERIOD SECOND < NOW() 
ORDER BY SYNC_DATE
LIMIT 0,'.intval($limit);
	}
}
?>
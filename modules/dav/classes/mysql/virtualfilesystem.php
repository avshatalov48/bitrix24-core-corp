<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/classes/general/virtualfilesystem.php");

class CDavVirtualFileSystem
	extends CAllDavVirtualFileSystem
{
	public static function GetList($arOrder = array("ID" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "PATH", "EXPIRES", "LOCK_OWNER", "LOCK_DEPTH", "LOCK_TYPE", "LOCK_SCOPE", "CREATED", "MODIFIED");

		static $arFields = array(
			"ID" => Array("FIELD" => "L.ID", "TYPE" => "string"),
			"PATH" => Array("FIELD" => "L.PATH", "TYPE" => "string"),
			"EXPIRES" => Array("FIELD" => "L.EXPIRES", "TYPE" => "int"),
			"LOCK_OWNER" => Array("FIELD" => "L.LOCK_OWNER", "TYPE" => "string"),
			"LOCK_DEPTH" => Array("FIELD" => "L.LOCK_DEPTH", "TYPE" => "string"),
			"LOCK_TYPE" => Array("FIELD" => "L.LOCK_TYPE", "TYPE" => "string"),
			"LOCK_SCOPE" => Array("FIELD" => "L.LOCK_SCOPE", "TYPE" => "string"),
			"CREATED" => Array("FIELD" => "L.CREATED", "TYPE" => "datetime"),
			"MODIFIED" => Array("FIELD" => "L.MODIFIED", "TYPE" => "datetime"),
		);

		$arSqls = CDav::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_dav_locks L ".
				"	".$arSqls["FROM"]." ";
			if ($arSqls["WHERE"] <> '')
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if ($arSqls["GROUPBY"] <> '')
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_dav_locks L ".
			"	".$arSqls["FROM"]." ";
		if ($arSqls["WHERE"] <> '')
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if ($arSqls["GROUPBY"] <> '')
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if ($arSqls["ORDERBY"] <> '')
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_dav_locks L ".
				"	".$arSqls["FROM"]." ";
			if ($arSqls["WHERE"] <> '')
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if ($arSqls["GROUPBY"] <> '')
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp);
			$cnt = 0;
			if ($arSqls["GROUPBY"] == '')
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql);
		}

		$dbRes = new CDavVirtualFileSystemResult($dbRes);
		return $dbRes;
	}
}
?>
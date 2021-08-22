<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/classes/general/connection.php");

class CDavConnection
	extends CAllDavConnection
{
	public static function Add($arFields)
	{
		global $DB, $APPLICATION;

		$res = self::ParseFields($arFields);
		if ($res !== true)
		{
			foreach ($res as $v)
			{
				$APPLICATION->ThrowException($v[0], $v[1]);
			}
			return;
		}

		$arInsert = $DB->PrepareInsert("b_dav_connections", $arFields);

		$strSql =
			"INSERT INTO b_dav_connections (".$arInsert[0].", CREATED, MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().", ".$DB->CurrentTimeFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$id = (int)$DB->LastID();
		if (($id > 0) && \Bitrix\Main\Loader::includeModule('calendar'))
		{
			$connectionType = \Bitrix\Calendar\Util::isGoogleConnection($arFields['ACCOUNT_TYPE'])
				? 'google'
				: (CCalendarSync::isYandex($arFields['SERVER_HOST'])
					? 'yandex'
					: 'caldav')
			;
			$connectionName = $connectionType . $id;
			\Bitrix\Calendar\Util::addPullEvent(
				'add_sync_connection',
				$arFields['ENTITY_ID'],
				[
					'syncInfo' => [
						$connectionName => [
							'type' => $connectionType,
						],
					]
				]
			);

			AddEventToStatFile('calendar', 'sync_connection_connected', $connectionType, '', 'server_connection');
		}

		return $id;
	}

	public static function GetList($arOrder = array("ID" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{

		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "ENTITY_TYPE", "ENTITY_ID", "ACCOUNT_TYPE", "NAME", "SERVER_SCHEME", "SERVER_HOST", "SERVER_PORT", "SYNC_TOKEN", "SERVER_USERNAME", "SERVER_PASSWORD", "SERVER_PATH", "CREATED", "MODIFIED", "SYNCHRONIZED", "LAST_RESULT");

		static $arFields = array(
			"ID" => Array("FIELD" => "N.ID", "TYPE" => "int"),
			"ENTITY_TYPE" => Array("FIELD" => "N.ENTITY_TYPE", "TYPE" => "string"),
			"ENTITY_ID" => Array("FIELD" => "N.ENTITY_ID", "TYPE" => "int"),
			"ACCOUNT_TYPE" => Array("FIELD" => "N.ACCOUNT_TYPE", "TYPE" => "string"),
			"NAME" => Array("FIELD" => "N.NAME", "TYPE" => "string"),
			"SERVER_SCHEME" => Array("FIELD" => "N.SERVER_SCHEME", "TYPE" => "string"),
			"SERVER_HOST" => Array("FIELD" => "N.SERVER_HOST", "TYPE" => "string"),
			"SERVER_PORT" => Array("FIELD" => "N.SERVER_PORT", "TYPE" => "int"),
			"SERVER_USERNAME" => Array("FIELD" => "N.SERVER_USERNAME", "TYPE" => "string"),
			"SERVER_PASSWORD" => Array("FIELD" => "N.SERVER_PASSWORD", "TYPE" => "string"),
			"SERVER_PATH" => Array("FIELD" => "N.SERVER_PATH", "TYPE" => "string"),
			"CREATED" => Array("FIELD" => "N.CREATED", "TYPE" => "datetime"),
			"MODIFIED" => Array("FIELD" => "N.MODIFIED", "TYPE" => "datetime"),
			"SYNCHRONIZED" => Array("FIELD" => "N.SYNCHRONIZED", "TYPE" => "datetime"),
			"LAST_RESULT" => Array("FIELD" => "N.LAST_RESULT", "TYPE" => "string"),
			"SYNC_TOKEN" => Array("FIELD" => "N.SYNC_TOKEN", "TYPE" => "string"),
		);

		$arSqls = CDav::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_dav_connections N ".
				"	".$arSqls["FROM"]." ";
			if ($arSqls["WHERE"] <> '')
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if ($arSqls["GROUPBY"] <> '')
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_dav_connections N ".
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
				"FROM b_dav_connections N ".
				"	".$arSqls["FROM"]." ";
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
			if (is_array($arNavStartParams) && (int)$arNavStartParams["nTopCount"] > 0)
			{
				$strSql .= "LIMIT " . (int)$arNavStartParams["nTopCount"];
			}

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbRes = new CDavConnectionResult($dbRes);
		return $dbRes;
	}
}
?>
<?

use Bitrix\Calendar\Sync\Google;
use Bitrix\Calendar\Sync\Office365;
use Bitrix\Main\DI\ServiceLocator;

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/classes/general/connection.php");

class CDavConnection extends CAllDavConnection
{
	private static $connectionData = [];

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
			return null;
		}

		$arInsert = $DB->PrepareInsert("b_dav_connections", $arFields);

		$strSql =
			"INSERT INTO b_dav_connections (".$arInsert[0].", CREATED, MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB::CurrentTimeFunction().", ".$DB::CurrentTimeFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$id = (int)$DB->LastID();
		if ($id > 0 && \Bitrix\Main\Loader::includeModule('calendar'))
		{
			// TODO: remove dependence from different calendar systems
			/** @var Google\Helper $googleHelper */
			$googleHelper = ServiceLocator::getInstance()->get('calendar.service.google.helper');
			/** @var Office365\Helper $office365Helper */
			$office365Helper = ServiceLocator::getInstance()->get('calendar.service.office365.helper');
			$iCloudHelper = ServiceLocator::getInstance()->get('calendar.service.icloud.helper');
			$caldavHelper = ServiceLocator::getInstance()->get('calendar.service.caldav.helper');
			$connectionType = $googleHelper->isGoogleConnection($arFields['ACCOUNT_TYPE'])
				? 'google'
				: ($office365Helper->isVendorConnection($arFields['ACCOUNT_TYPE'])
					? 'office365'
					: ($iCloudHelper->isVendorConnection($arFields['ACCOUNT_TYPE'])
						? 'icloud'
						: ($caldavHelper->isYandex($arFields['SERVER_HOST'])
							? 'yandex'
							: 'caldav'
						)
					)
				)
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

	public static function GetList($arOrder = ["ID" => "ASC"], $arFilter = [], $arGroupBy = null, $arNavStartParams = null, $arSelectFields = [])
	{
		global $DB;

		if (
			is_array($arFilter)
			&& isset($arFilter['ID'], self::$connectionData[(int)$arFilter['ID']])
			&& (int)$arFilter['ID']
			&& self::$connectionData[(int)$arFilter['ID']]
		)
		{
			return self::$connectionData[(int)$arFilter['ID']];
		}

		if (is_array($arSelectFields) && empty($arSelectFields))
		{
			$arSelectFields = [
				"ID",
				"ENTITY_TYPE",
				"ENTITY_ID",
				"ACCOUNT_TYPE",
				"NAME",
				"SERVER_SCHEME",
				"SERVER_HOST",
				"SERVER_PORT",
				"SYNC_TOKEN",
				"SERVER_USERNAME",
				"SERVER_PASSWORD",
				"SERVER_PATH",
				"CREATED",
				"MODIFIED",
				"SYNCHRONIZED",
				"LAST_RESULT",
				"IS_DELETED",
				"NEXT_SYNC_TRY",
			];
		}

		static $arFields = [
			"ID" => ["FIELD" => "N.ID", "TYPE" => "int"],
			"ENTITY_TYPE" => ["FIELD" => "N.ENTITY_TYPE", "TYPE" => "string"],
			"ENTITY_ID" => ["FIELD" => "N.ENTITY_ID", "TYPE" => "int"],
			"ACCOUNT_TYPE" => ["FIELD" => "N.ACCOUNT_TYPE", "TYPE" => "string"],
			"NAME" => ["FIELD" => "N.NAME", "TYPE" => "string"],
			"SERVER_SCHEME" => ["FIELD" => "N.SERVER_SCHEME", "TYPE" => "string"],
			"SERVER_HOST" => ["FIELD" => "N.SERVER_HOST", "TYPE" => "string"],
			"SERVER_PORT" => ["FIELD" => "N.SERVER_PORT", "TYPE" => "int"],
			"SERVER_USERNAME" => ["FIELD" => "N.SERVER_USERNAME", "TYPE" => "string"],
			"SERVER_PASSWORD" => ["FIELD" => "N.SERVER_PASSWORD", "TYPE" => "string"],
			"SERVER_PATH" => ["FIELD" => "N.SERVER_PATH", "TYPE" => "string"],
			"CREATED" => ["FIELD" => "N.CREATED", "TYPE" => "datetime"],
			"MODIFIED" => ["FIELD" => "N.MODIFIED", "TYPE" => "datetime"],
			"SYNCHRONIZED" => ["FIELD" => "N.SYNCHRONIZED", "TYPE" => "datetime"],
			"LAST_RESULT" => ["FIELD" => "N.LAST_RESULT", "TYPE" => "string"],
			"SYNC_TOKEN" => ["FIELD" => "N.SYNC_TOKEN", "TYPE" => "string"],
			"IS_DELETED" => ["FIELD" => "N.IS_DELETED", "TYPE" => "string"],
			"NEXT_SYNC_TRY" => ["FIELD" => "N.NEXT_SYNC_TRY", "TYPE" => "datetime"],
		];

		$arSqls = CDav::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
		$strSql =
			"SELECT " . $arSqls["SELECT"] . " "
			. "FROM b_dav_connections N "
			. "	" . $arSqls["FROM"] . " ";
		if ($arSqls["WHERE"])
		{
			$strSql .= "WHERE " . $arSqls["WHERE"] . " ";
		}
		if ($arSqls["GROUPBY"])
		{
			$strSql .= "GROUP BY " . $arSqls["GROUPBY"] . " ";
		}
		if (is_array($arGroupBy) && empty($arGroupBy))
		{
			$dbRes = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				return $arRes["CNT"];
			}

			return false;
		}

		if ($arSqls["ORDERBY"])
		{
			$strSql .= "ORDER BY " . $arSqls["ORDERBY"] . " ";
		}

		if (is_array($arNavStartParams) && !(int)$arNavStartParams["nTopCount"])
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT "
				. "FROM b_dav_connections N "
				. "	" . $arSqls["FROM"] . " ";
			if ($arSqls["WHERE"])
			{
				$strSql_tmp .= "WHERE " . $arSqls["WHERE"] . " ";
			}
			if ($arSqls["GROUPBY"])
			{
				$strSql_tmp .= "GROUP BY " . $arSqls["GROUPBY"] . " ";
			}

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (!$arSqls["GROUPBY"])
			{
				if ($arRes = $dbRes->Fetch())
				{
					$cnt = $arRes["CNT"];
				}
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

		if (
			$dbRes
			&& is_array($arFilter)
			&& isset($arFilter['ID'])
			&& (int)$arFilter['ID']
		)
		{
			$result = (new CDavConnectionResult($dbRes))->Fetch();
			if ($result && $result['ID'])
			{
				self::$connectionData[(int)$arFilter['ID']] = $result;
			}

			return $result;
		}

		return new CDavConnectionResult($dbRes);
	}
}

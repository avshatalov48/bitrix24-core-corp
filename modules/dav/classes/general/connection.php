<?

IncludeModuleLangFile(__FILE__);

class CAllDavConnection
{
	public static function ParseFields(&$arFields, $id = 0)
	{
		$id = (int)$id;
		$updateMode = ($id > 0);
		$addMode = !$updateMode;

		$arError = array();

		if ($updateMode)
		{
			$arConnectionOld = self::GetById($id);
			if (is_null($arConnectionOld))
			{
				$arError[] = ["updateMode", "updateMode"];
			}
			//throw new CDavInvalidOperationException("updateMode");
		}

		if (
			($addMode && !isset($arFields['NAME']))
			|| (isset($arFields['NAME']) && !$arFields['NAME'])
		)
		{
			$arError[] = [
				GetMessage('DAV_EXP_NAME'),
				"NULL_NAME"
			];
		}
		//throw new CDavArgumentNullException("NAME", GetMessage('DAV_EXP_NAME'));

		if ($addMode && !isset($arFields['ACCOUNT_TYPE']))
		{
			$arError[] = [
				GetMessage('DAV_EXP_ACCOUNT_TYPE'),
				"NULL_ACCOUNT_TYPE"
			];
		}
		//throw new CDavArgumentNullException("ACCOUNT_TYPE", GetMessage('DAV_EXP_ACCOUNT_TYPE'));

		if (isset($arFields['ACCOUNT_TYPE']))
		{
			$arFields["ACCOUNT_TYPE"] = mb_strtolower($arFields["ACCOUNT_TYPE"]);
			if (!in_array($arFields['ACCOUNT_TYPE'], [
				'caldav',
				'ical',
				\Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV,
				\Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_API,
				\Bitrix\Calendar\Sync\Office365\Helper::ACCOUNT_TYPE,
				\Bitrix\Calendar\Sync\Icloud\Helper::ACCOUNT_TYPE,
			], true)
		)
			{
				$arError[] = [
					GetMessage('DAV_EXP_ACCOUNT_TYPE_OOR'),
					"OOR_ACCOUNT_TYPE"
				];
			}
			//throw new CDavArgumentOutOfRangeException("ACCOUNT_TYPE", GetMessage('DAV_EXP_ACCOUNT_TYPE'), array("caldav", "ical"));
		}

		if ($addMode && !isset($arFields['ENTITY_TYPE']))
		{
			$arError[] = [
				GetMessage('DAV_EXP_ENTITY_TYPE'),
				"NULL_ENTITY_TYPE"
			];
		}
		//throw new CDavArgumentNullException("ENTITY_TYPE", GetMessage('DAV_EXP_ENTITY_TYPE'));

		if (isset($arFields['ENTITY_TYPE']))
		{
			$arFields["ENTITY_TYPE"] = mb_strtolower($arFields["ENTITY_TYPE"]);
			if (!in_array($arFields["ENTITY_TYPE"], ["user", "group"]))
			{
				$arError[] = [
					GetMessage('DAV_EXP_ENTITY_TYPE_OOR'),
					"OOR_ENTITY_TYPE"
				];
			}
			//throw new CDavArgumentOutOfRangeException("ENTITY_TYPE", GetMessage('DAV_EXP_ENTITY_TYPE'), array("user", "group"));
		}

		if ($addMode && !isset($arFields['ENTITY_ID']))
		{
			$arError[] = [
				GetMessage('DAV_EXP_ENTITY_ID'),
				"NULL_ENTITY_ID"
			];
		}
		//throw new CDavArgumentNullException("ENTITY_ID", GetMessage('DAV_EXP_ENTITY_ID'));

		if (isset($arFields['ENTITY_ID']))
		{
			$entityId = (int)$arFields["ENTITY_ID"];
			if (!$entityId || ($entityId."!" !== $arFields["ENTITY_ID"]."!"))
			{
				$arError[] = [
					GetMessage('DAV_EXP_ENTITY_ID_TYPE'),
					"TYPE_ENTITY_ID"
				];
			}
			//throw new CDavArgumentTypeException("ENTITY_ID", GetMessage('DAV_EXP_ENTITY_ID'), "int");
		}

		if (isset($arFields['SERVER']))
		{
			$arServer = [
				'host' => null,
				'scheme' => null,
				'port' => null,
				'path' => null,
			];
			$parsedUrl = parse_url($arFields["SERVER"]);
			$arServer = array_merge($arServer, $parsedUrl);

			$arFields["SERVER_SCHEME"] = $arServer["scheme"];
			$arFields["SERVER_HOST"] = $arServer["host"];
			$arFields["SERVER_PORT"] = $arServer["port"];
			$arFields["SERVER_PATH"] = $arServer["path"];
			unset($arFields["SERVER"]);
		}

		if ($addMode && !isset($arFields['SERVER_SCHEME']))
		{
			$arError[] = [
				GetMessage('DAV_EXP_SERVER_SCHEME'),
				"NULL_SERVER_SCHEME"
			];
		}
		//throw new CDavArgumentNullException("SERVER_SCHEME", GetMessage('DAV_EXP_SERVER_SCHEME'));

		if (isset($arFields['SERVER_SCHEME']))
		{
			$arFields["SERVER_SCHEME"] = mb_strtolower($arFields["SERVER_SCHEME"]);
			if (!in_array($arFields["SERVER_SCHEME"], array("http", "https")))
			{
				$arError[] = [
					GetMessage('DAV_EXP_SERVER_SCHEME_OOR'),
					"OOR_SERVER_SCHEME"
				];
			}
			//throw new CDavArgumentOutOfRangeException("SERVER_SCHEME", GetMessage('DAV_EXP_SERVER_SCHEME'), array("http", "https"));
		}

		if (
			($addMode && !isset($arFields['SERVER_HOST']))
			|| (isset($arFields['SERVER_HOST']) && !$arFields["SERVER_HOST"])
		)
		{
			$arError[] = [
				GetMessage('DAV_EXP_SERVER_HOST'),
				"NULL_SERVER_HOST"
			];
		}
		//throw new CDavArgumentNullException("SERVER_HOST", GetMessage('DAV_EXP_SERVER_HOST'));

		if (
			($addMode && !isset($arFields['SERVER_PORT']))
			|| (isset($arFields['SERVER_PORT']) && !(int)$arFields["SERVER_PORT"])
		)
		{
			if ($updateMode && !is_set($arFields, "SERVER_SCHEME"))
			{
				$arFields["SERVER_SCHEME"] = $arConnectionOld["SERVER_SCHEME"];
			}

			if ($arFields["SERVER_SCHEME"] === 'https')
			{
				$arFields["SERVER_PORT"] = 443;
			}
			else
			{
				$arFields["SERVER_PORT"] = 80;
			}
		}
		elseif (isset($arFields['SERVER_PORT']))
		{
			if ($arFields["SERVER_PORT"]."!" != (int)$arFields["SERVER_PORT"]."!")
			{
				$arError[] = [
					GetMessage('DAV_EXP_SERVER_PORT'),
					"TYPE_SERVER_PORT"
				];
			}
			//throw new CDavArgumentTypeException("SERVER_PORT", GetMessage('DAV_EXP_SERVER_PORT'), "int");
		}

		if (
			($addMode && !isset($arFields['SERVER_PATH']))
			|| (isset($arFields['SERVER_PATH']) && !$arFields["SERVER_PATH"])
		)
		{
			$arFields["SERVER_PATH"] = "/";
		}

		if (!empty($arError))
		{
			return $arError;
		}

		return true;
	}

	public static function MarkSynchronized($id)
	{
		if ($tzEnabled = CTimeZone::Enabled())
		{
			CTimeZone::Disable();
		}

		self::Update(
			$id,
			array("SYNCHRONIZED" => ConvertTimeStamp(time(), "FULL")),
			false
		);

		if ($tzEnabled)
		{
			CTimeZone::Enable();
		}
	}

	/**
	 * @param $id
	 * @param $result
	 * @param null $syncToken
	 * @throws CDavArgumentNullException
	 */
	public static function SetLastResult($id, $result, $syncToken = null): void
	{
		if ($tzEnabled = CTimeZone::Enabled())
		{
			CTimeZone::Disable();
		}

		self::Update(
			$id,
			["LAST_RESULT" => $result, "SYNCHRONIZED" => ConvertTimeStamp(time(), "FULL"), "SYNC_TOKEN" => $syncToken],
			false
		);

		if ($tzEnabled)
		{
			CTimeZone::Enable();
		}
	}

	public static function Update($id, $arFields, $bModifyDate = true)
	{
		global $DB, $APPLICATION;

		$id = (int)$id;
		if (!$id)
		{
			throw new CDavArgumentNullException("id");
		}

		$r = self::ParseFields($arFields, $id);
		if ($r !== true)
		{
			foreach ($r as $v)
			{
				$APPLICATION->ThrowException($v[0], $v[1]);
			}

			return false;
		}

		$strUpdate = $DB->PrepareUpdate("b_dav_connections", $arFields);
		if ($strUpdate <> '')
		{
			$strSql =
				"UPDATE b_dav_connections SET ".
				"	".$strUpdate." ".
				($bModifyDate ? ", MODIFIED = ".$DB::CurrentTimeFunction()." " : "").
				"WHERE ID = ".$id." ";

			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $id;
	}

	public static function Delete($id)
	{
		global $DB;
		return $DB->Query("DELETE FROM b_dav_connections WHERE ID = ".(int) $id." ", true);
	}

	public static function GetById($id)
	{
		$id = (int)$id;
		if (!$id)
		{
			throw new CDavArgumentNullException("id");
		}

		$dbResult = CDavConnection::GetList(
			["ID" => "ASC"],
			["ID" => $id]
		);

		if ($dbResult)
		{
			return $dbResult;
		}

		return null;
	}
}

class CDavConnectionResult extends CDBResult
{
	public function __construct($res)
	{
		parent::__construct($res);
	}

	function Fetch()
	{
		$res = parent::Fetch();

		if (
			$res
			&& isset(
				$res['SERVER_SCHEME'],
				$res['SERVER_HOST'],
				$res['SERVER_PORT'],
				$res['SERVER_PATH']
			)
		)
		{
			$res["SERVER"] = $res["SERVER_SCHEME"]."://".$res["SERVER_HOST"];
			if (
				($res["SERVER_SCHEME"] === "https" && (int)$res["SERVER_PORT"] !== 443)
				|| ($res["SERVER_SCHEME"] === "http" && (int)$res["SERVER_PORT"] !== 80)
			)
			{
				$res["SERVER"] .= ":" . $res["SERVER_PORT"];
			}
			$res["SERVER"] .= $res["SERVER_PATH"];
		}

		return $res;
	}
}

<?

IncludeModuleLangFile(__FILE__);

class CAllDavConnection
{
	protected static function ParseFields(&$arFields, $id = 0)
	{
		global $DB;

		$id = intval($id);
		$updateMode = ($id > 0);
		$addMode = !$updateMode;

		$arError = array();

		if ($updateMode)
		{
			$arConnectionOld = self::GetById($id);
			if (is_null($arConnectionOld))
				$arError[] = array("updateMode", "updateMode");
				//throw new CDavInvalidOperationException("updateMode");
		}

		if ($addMode && !is_set($arFields, "NAME") || is_set($arFields, "NAME") && (strlen($arFields["NAME"]) <= 0))
			$arError[] = array(GetMessage('DAV_EXP_NAME'), "NULL_NAME");
			//throw new CDavArgumentNullException("NAME", GetMessage('DAV_EXP_NAME'));

		if ($addMode && !is_set($arFields, "ACCOUNT_TYPE"))
			$arError[] = array(GetMessage('DAV_EXP_ACCOUNT_TYPE'), "NULL_ACCOUNT_TYPE");
			//throw new CDavArgumentNullException("ACCOUNT_TYPE", GetMessage('DAV_EXP_ACCOUNT_TYPE'));

		if (is_set($arFields, "ACCOUNT_TYPE"))
		{
			$arFields["ACCOUNT_TYPE"] = strtolower($arFields["ACCOUNT_TYPE"]);
			if (!in_array($arFields['ACCOUNT_TYPE'], array('caldav', 'ical', 'caldav_google_oauth', 'google_api_oauth')))
				$arError[] = array(GetMessage('DAV_EXP_ACCOUNT_TYPE_OOR'), "OOR_ACCOUNT_TYPE");
				//throw new CDavArgumentOutOfRangeException("ACCOUNT_TYPE", GetMessage('DAV_EXP_ACCOUNT_TYPE'), array("caldav", "ical"));
		}

		if ($addMode && !is_set($arFields, "ENTITY_TYPE"))
			$arError[] = array(GetMessage('DAV_EXP_ENTITY_TYPE'), "NULL_ENTITY_TYPE");
			//throw new CDavArgumentNullException("ENTITY_TYPE", GetMessage('DAV_EXP_ENTITY_TYPE'));

		if (is_set($arFields, "ENTITY_TYPE"))
		{
			$arFields["ENTITY_TYPE"] = strtolower($arFields["ENTITY_TYPE"]);
			if (!in_array($arFields["ENTITY_TYPE"], array("user", "group")))
				$arError[] = array(GetMessage('DAV_EXP_ENTITY_TYPE_OOR'), "OOR_ENTITY_TYPE");
				//throw new CDavArgumentOutOfRangeException("ENTITY_TYPE", GetMessage('DAV_EXP_ENTITY_TYPE'), array("user", "group"));
		}

		if ($addMode && !is_set($arFields, "ENTITY_ID"))
			$arError[] = array(GetMessage('DAV_EXP_ENTITY_ID'), "NULL_ENTITY_ID");
			//throw new CDavArgumentNullException("ENTITY_ID", GetMessage('DAV_EXP_ENTITY_ID'));

		if (is_set($arFields, "ENTITY_ID"))
		{
			$entityId = intval($arFields["ENTITY_ID"]);
			if (($entityId <= 0) || ($entityId."!" != $arFields["ENTITY_ID"]."!"))
				$arError[] = array(GetMessage('DAV_EXP_ENTITY_ID_TYPE'), "TYPE_ENTITY_ID");
				//throw new CDavArgumentTypeException("ENTITY_ID", GetMessage('DAV_EXP_ENTITY_ID'), "int");
		}

		if (is_set($arFields, "SERVER"))
		{
			$arServer = parse_url($arFields["SERVER"]);
			$arFields["SERVER_SCHEME"] = $arServer["scheme"];
			$arFields["SERVER_HOST"] = $arServer["host"];
			$arFields["SERVER_PORT"] = $arServer["port"];
			$arFields["SERVER_PATH"] = $arServer["path"];
			unset($arFields["SERVER"]);
		}

		if ($addMode && !is_set($arFields, "SERVER_SCHEME"))
			$arError[] = array(GetMessage('DAV_EXP_SERVER_SCHEME'), "NULL_SERVER_SCHEME");
			//throw new CDavArgumentNullException("SERVER_SCHEME", GetMessage('DAV_EXP_SERVER_SCHEME'));

		if (is_set($arFields, "SERVER_SCHEME"))
		{
			$arFields["SERVER_SCHEME"] = strtolower($arFields["SERVER_SCHEME"]);
			if (!in_array($arFields["SERVER_SCHEME"], array("http", "https")))
				$arError[] = array(GetMessage('DAV_EXP_SERVER_SCHEME_OOR'), "OOR_SERVER_SCHEME");
				//throw new CDavArgumentOutOfRangeException("SERVER_SCHEME", GetMessage('DAV_EXP_SERVER_SCHEME'), array("http", "https"));
		}

		if ($addMode && !is_set($arFields, "SERVER_HOST") || is_set($arFields, "SERVER_HOST") && (strlen($arFields["SERVER_HOST"]) <= 0))
			$arError[] = array(GetMessage('DAV_EXP_SERVER_HOST'), "NULL_SERVER_HOST");
			//throw new CDavArgumentNullException("SERVER_HOST", GetMessage('DAV_EXP_SERVER_HOST'));

		if ($addMode && !is_set($arFields, "SERVER_PORT") || is_set($arFields, "SERVER_PORT") && (intval($arFields["SERVER_PORT"]) <= 0))
		{
			if ($updateMode && !is_set($arFields, "SERVER_SCHEME"))
				$arFields["SERVER_SCHEME"] = $arConnectionOld["SERVER_SCHEME"];

			if ($arFields["SERVER_SCHEME"] == 'https')
				$arFields["SERVER_PORT"] = 443;
			else
				$arFields["SERVER_PORT"] = 80;
		}
		elseif (is_set($arFields, "SERVER_PORT"))
		{
			if ($arFields["SERVER_PORT"]."!" != intval($arFields["SERVER_PORT"])."!")
				$arError[] = array(GetMessage('DAV_EXP_SERVER_PORT'), "TYPE_SERVER_PORT");
				//throw new CDavArgumentTypeException("SERVER_PORT", GetMessage('DAV_EXP_SERVER_PORT'), "int");
		}

		if ($addMode && !is_set($arFields, "SERVER_PATH") || is_set($arFields, "SERVER_PATH") && (strlen($arFields["SERVER_PATH"]) <= 0))
			$arFields["SERVER_PATH"] = "/";

		if (count($arError) > 0)
			return $arError;

		return true;
	}

	public static function MarkSynchronized($id)
	{
		self::Update(
			$id,
			array("SYNCHRONIZED" => ConvertTimeStamp(time(), "FULL")),
			false
		);
	}

	public static function SetLastResult($id, $result, $syncToken = null)
	{
		self::Update(
			$id,
			array("LAST_RESULT" => $result, "SYNCHRONIZED" => ConvertTimeStamp(time(), "FULL"), "SYNC_TOKEN" => $syncToken),
			false
		);
	}

	public static function Update($id, $arFields, $bModifyDate = true)
	{
		global $DB, $APPLICATION;

		$id = intval($id);
		if ($id <= 0)
			throw new CDavArgumentNullException("id");

		$r = self::ParseFields($arFields, $id);
		if ($r !== true)
		{
			foreach ($r as $v)
				$APPLICATION->ThrowException($v[0], $v[1]);
			return;
		}

		$strUpdate = $DB->PrepareUpdate("b_dav_connections", $arFields);
		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_dav_connections SET ".
				"	".$strUpdate." ".
				($bModifyDate ? ", MODIFIED = ".$DB->CurrentTimeFunction()." " : "").
				"WHERE ID = ".$id." ";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $id;
	}

	public static function Delete($id)
	{
		global $DB;
		return $DB->Query("DELETE FROM b_dav_connections WHERE ID = ".intval($id)." ", true);
	}

	public static function GetById($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new CDavArgumentNullException("id");

		$dbResult = CDavConnection::GetList(array(), array("ID" => $id));
		if ($arResult = $dbResult->Fetch())
			return $arResult;

		return null;
	}
}

class CDavConnectionResult extends CDBResult
{
	public function __construct($res)
	{
		parent::CDBResult($res);
	}

	function Fetch()
	{
		$res = parent::Fetch();

		if ($res)
		{
			if (array_key_exists("SERVER_SCHEME", $res) && array_key_exists("SERVER_HOST", $res) && array_key_exists("SERVER_PORT", $res) && array_key_exists("SERVER_PATH", $res))
			{
				$res["SERVER"] = $res["SERVER_SCHEME"]."://".$res["SERVER_HOST"];
				if ($res["SERVER_SCHEME"] == "https" && $res["SERVER_PORT"] != 443 || $res["SERVER_SCHEME"] == "http" && $res["SERVER_PORT"] != 80)
					$res["SERVER"] .= ":".$res["SERVER_PORT"];
				$res["SERVER"] .= $res["SERVER_PATH"];
			}
		}

		return $res;
	}
}
?>
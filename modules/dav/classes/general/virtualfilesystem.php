<?
class CAllDavVirtualFileSystem
{
	static protected $lockCache;

	public static function GetLockPath($app, $entry)
	{
		return "/apps/$app/entry/$entry";
	}

	public static function CheckLock($path)
	{
		if (isset(self::$lockCache[$path]))
			return self::$lockCache[$path];

		$dbResult = CDavVirtualFileSystem::GetList(array(), array("PATH" => $path));

		if ($arResult = $dbResult->Fetch())
		{
			if ($arResult['EXPIRES'] < time())
			{
				self::Delete($arResult['ID']);
				$arResult = false;
			}
		}

		return self::$lockCache[$path] = $arResult;
	}

	public static function Lock($path, $token, &$timeout, $owner, $scope, $type)
	{
		if (strlen($path) <= 0)
			return false;

		unset(self::$lockCache[$path]);

		if ($timeout < 1000000)				// < 1000000 is a relative timestamp, so we add the current time
			$timeout += time();

		if (($lock = self::CheckLock($path)) && ($lock['LOCK_SCOPE'] == 'exclusive' || $scope == 'exclusive'))
		{
			return false;
		}
		else
		{
			try
			{
				self::Add(array(
					"ID" => $token,
					"PATH" => $path,
					"EXPIRES" => $timeout,
					"LOCK_OWNER" => $owner,
					"LOCK_TYPE" => $type,
					"LOCK_SCOPE" => $scope,
				));

				return true;
			}
			catch (Exception $e)
			{
			}

			return false;
		}
	}

	public static function UpdateLock($path, $token, &$timeout, &$owner, &$scope, &$type)
	{
		if (strlen($path) <= 0 || strlen($token) <= 0)
			return false;

		unset(self::$lockCache[$path]);

		if ($timeout < 1000000)				// < 1000000 is a relative timestamp, so we add the current time
			$timeout += time();

		$dbResult = CDavVirtualFileSystem::GetList(array(), array("PATH" => $path, "ID" => $token), false, false, array("LOCK_OWNER", "LOCK_DEPTH", "LOCK_TYPE", "LOCK_SCOPE"));
		if ($arResult = $dbResult->Fetch())
		{
			$owner = $arResult['LOCK_OWNER'];
			$scope = $arResult['LOCK_SCOPE'];
			$type = $arResult['LOCK_TYPE'];

			try
			{
				self::Update($token, array("EXPIRES" => $timeout));
				return true;
			}
			catch (Exception $e)
			{
			}
		}

		return false;
	}

	public static function Unlock($path, $token)
	{
		$dbResult = CDavVirtualFileSystem::GetList(array(), array("PATH" => $path, "ID" => $token), false, false, array("ID"));
		if ($arResult = $dbResult->Fetch())
		{
			self::Delete($arResult["ID"]);
			unset(self::$lockCache[$path]);
		}

		return true;
	}

	protected static function ParseFields(&$arFields, $mode = "add")
	{
		global $DB;

		$mode = strtoupper($mode);
		$updateMode = ($mode != "add");
		$addMode = !$updateMode;

		if (is_set($arFields, "LOCK_TYPE"))
		{
			$arFields["LOCK_TYPE"] = strtoupper($arFields["LOCK_TYPE"]);
			if ($arFields["LOCK_TYPE"] == "WRITE")
				$arFields["LOCK_TYPE"] = "W";
			if ($arFields["LOCK_TYPE"] == "READ")
				$arFields["LOCK_TYPE"] = "R";

			if (!in_array($arFields["LOCK_TYPE"], array("W", "R")))
				throw new Exception("LOCK_TYPE");
		}

		if (is_set($arFields, "LOCK_SCOPE"))
		{
			$arFields["LOCK_SCOPE"] = strtoupper($arFields["LOCK_SCOPE"]);
			if ($arFields["LOCK_SCOPE"] == "EXCLUSIVE")
				$arFields["LOCK_SCOPE"] = "E";
			if ($arFields["LOCK_SCOPE"] == "SHARED")
				$arFields["LOCK_SCOPE"] = "S";

			if (!in_array($arFields["LOCK_SCOPE"], array("E", "S")))
				throw new Exception("LOCK_SCOPE");
		}

		if (is_set($arFields, "LOCK_DEPTH"))
		{
			if (is_numeric($arFields["LOCK_DEPTH"]))
				$arFields["LOCK_DEPTH"] = intval($arFields["LOCK_DEPTH"]);
			elseif (strtoupper($arFields["LOCK_DEPTH"]) == "INFINITE" || strtoupper($arFields["LOCK_DEPTH"]) == "I")
				$arFields["LOCK_DEPTH"] = "I";
			else
				throw new Exception("LOCK_DEPTH");
		}

		if ($addMode && !is_set($arFields, "ID"))
			throw new Exception("ID");
		if ($addMode && !is_set($arFields, "PATH"))
			throw new Exception("PATH");
	}

	public static function Add($arFields)
	{
		global $DB;

		self::ParseFields($arFields, "add");

		$arInsert = $DB->PrepareInsert("b_dav_locks", $arFields);

		$strSql =
			"INSERT INTO b_dav_locks (".$arInsert[0].", CREATED, MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().", ".$DB->CurrentTimeFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $arFields["ID"];
	}

	public static function Update($id, $arFields)
	{
		global $DB;

		$id = trim($id);
		if (strlen($id) <= 0)
			throw new Exception("id");

		self::ParseFields($arFields, "update");

		$strUpdate = $DB->PrepareUpdate("b_dav_locks", $arFields);

		$strSql =
			"UPDATE b_dav_locks SET ".
			"	".$strUpdate.", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = '".$DB->ForSql($id)."' ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $id;
	}

	public static function Delete($id)
	{
		global $DB;
		$DB->Query("DELETE FROM b_dav_locks WHERE ID = '".$DB->ForSql($id)."' ", true);
	}
}

class CDavVirtualFileSystemResult extends CDBResult
{
	public function __construct($res)
	{
		parent::CDBResult($res);
	}

	public function Fetch()
	{
		$res = parent::Fetch();

		if ($res)
		{
			$res["LOCK_TYPE"] = ($res['LOCK_TYPE'] == "W") ? 'write' : 'read';
			$res["LOCK_SCOPE"] = ($res['LOCK_SCOPE'] == "E") ? 'exclusive' : 'shared';
			$res["LOCK_DEPTH"] = ($res['LOCK_DEPTH'] == "I") ? 'infinite' : 0;
		}

		return $res;
	}
}
?>
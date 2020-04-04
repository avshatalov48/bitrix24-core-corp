<?
define("GW_MAXIMUM_PRIVILEGES", "65535");
define("GW_ADDRESSBOOK_MAXIMUM_PRIVILEGES", "1023");

class CDav
{
	public static function OnBeforePrologWebDav()
	{
		global $USER, $APPLICATION;

		if (isset($_SERVER["PHP_AUTH_USER"]) &&
			(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true) &&
			(static::IsDavHeaders("check_all") ||
			!$USER->IsAuthorized()))
		{
			if (strlen($_SERVER["PHP_AUTH_USER"]) > 0 and
				strlen($_SERVER["PHP_AUTH_PW"]) > 0)
			{
				if (strpos($_SERVER["PHP_AUTH_USER"], $_SERVER['HTTP_HOST']."\\") === 0)
				{
					$_SERVER["PHP_AUTH_USER"] = str_replace($_SERVER['HTTP_HOST']."\\", "", $_SERVER["PHP_AUTH_USER"]);
				}
				elseif (strpos($_SERVER["PHP_AUTH_USER"], $_SERVER['SERVER_NAME']."\\") === 0)
				{
					$_SERVER["PHP_AUTH_USER"] = str_replace($_SERVER['SERVER_NAME']."\\", "", $_SERVER["PHP_AUTH_USER"]);
				}
				$arAuthResult = $USER->Login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], "N");
				$APPLICATION->arAuthResult = $arAuthResult;
			}
		}

		if (
			($_SERVER['REQUEST_METHOD']=='OPTIONS' || $_SERVER['REQUEST_METHOD']=='PROPFIND') &&
			(
				(
					strlen($_SERVER["REAL_FILE_PATH"])<=0 &&
					substr($_SERVER['REQUEST_URI'], -1, 1)=='/'
				) || (
					strpos($_SERVER['REQUEST_URI'], 'personal')!==false &&
					strlen($_SERVER["REAL_FILE_PATH"])<=0 &&
					!file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'])
				) // windows scans all the path up to the root, fails if 404, and we have it in /company/personal/...
			)
		)
		{
			$res = CUrlRewriter::GetList(Array("QUERY"=>$_SERVER['REQUEST_URI']));
			$good_res = true;
			$file_path = "";
			foreach($res as $res_detail)
			{
				if(strpos($res_detail["ID"], "disk")!==false || strpos($res_detail["ID"], "dav")!==false || strpos($res_detail["ID"], "webdav")!==false || strpos($res_detail["ID"], "socialnetwork")!==false)
				{
					$good_res = (!$USER->IsAuthorized()/* && $APPLICATION->GetFileAccessPermission(Array(SITE_ID, $res_detail["PATH"]), Array(2)) < "R"*/);
					break;
				}
			}

			if($good_res)
			{
				header("MS-Author-Via: DAV");
				if ( ( strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft-WebDAV-MiniRedir") !== false ) && // for office 2007, windows xp
					($_SERVER['REQUEST_METHOD'] == "OPTIONS") )
				{
					CDavWebDavServer::showOptions();
					die();
				}

				if($_SERVER['REQUEST_METHOD']!='PROPFIND')
				{
					if(!$USER->IsAuthorized())
					{
						static::SetAuthHeader();
						die();
					}
					CDavWebDavServer::showOptions();
					die();
				}

				if($_SERVER['REQUEST_METHOD']=='PROPFIND')
				{
					if(!$USER->IsAuthorized())
					{
						static::SetAuthHeader();
						die();
					}

				CDavResponse::sendStatus('207 Multi-Status');
echo '<?xml version="1.0" encoding="utf-8" ?>
<D:multistatus xmlns:D="DAV:" xmlns:Office="urn:schemas-microsoft-com:office:office" xmlns:Repl="http://schemas.microsoft.com/repl/" xmlns:Z="urn:schemas-microsoft-com:">
<D:response>
	<D:href>http://'.htmlspecialcharsbx($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']).'</D:href>
	<D:propstat>
		<D:prop>
			<D:displayname></D:displayname>
			<D:lockdiscovery/><D:supportedlock/>
			<D:isFolder>t</D:isFolder>
			<D:iscollection>1</D:iscollection>
			<D:ishidden>0</D:ishidden>
			<D:getcontenttype>application/octet-stream</D:getcontenttype>
			<D:getcontentlength>0</D:getcontentlength>
			<D:resourcetype><D:collection/></D:resourcetype>
			<Repl:authoritative-directory>t</Repl:authoritative-directory>
			<D:getlastmodified>2008-10-29T13:58:59Z</D:getlastmodified>
			<D:creationdate>2008-10-29T13:58:59Z</D:creationdate>
			<Repl:repl-uid>rid:{D77F5F6A-44A9-4015-AB49-4D3A439808C1}</Repl:repl-uid>
			<Repl:resourcetag>rt:D77F5F6A-44A9-4015-AB49-4D3A439808C1@00000000000</Repl:resourcetag>
			<D:getetag>&quot;{D77F5F6A-44A9-4015-AB49-4D3A439808C1},0&quot;</D:getetag>
		</D:prop>
		<D:status>HTTP/1.1 200 OK</D:status>
	</D:propstat>
</D:response>
</D:multistatus>';
					die();
				}
			}
		}
		elseif (static::IsDavHeaders("check_all"))
		{
			if (!$USER->IsAuthorized())
			{
				$res = CUrlRewriter::GetList(Array("QUERY"=>$_SERVER['REQUEST_URI']));
				$good_res = true;
				$file_path = "";
				foreach($res as $res_detail)
				{
					if(strpos($res_detail["ID"], "dav")!==false || strpos($res_detail["ID"], "disk")!==false || strpos($res_detail["ID"], "webdav")!==false || strpos($res_detail["ID"], "socialnetwork")!==false)
					{
						$good_res = (!$USER->IsAuthorized()/* && $APPLICATION->GetFileAccessPermission(Array(SITE_ID, $res_detail["PATH"]), Array(2)) < "R"*/);
						break;
					}
				}
				if ($good_res)
				{
					static::SetAuthHeader();
					die();
				}
			}

			return true;
		}
	}

	public static function SetAuthHeader()
	{
		$digest = static::isDigestEnabled();
		CHTTP::SetAuthHeader($digest);
	}

	public static function isDigestEnabled()
	{
		$digest = true;
		if (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft-WebDAV-MiniRedir") !== false)
		{
			if (preg_match("/([^\/]*)\/(\d+).(\d+).(\d+)/", $_SERVER['HTTP_USER_AGENT'], $matches) > 0) // Redir/5.1.2600
			{
				if (intval($matches[2]) < 6) // less then vista
				{
					$digest = false;
				}
			}
		}
		elseif (
			(strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Data Access Internet Publishing Provider") !== false)
			|| (
				(self::GetWindowsVersion() === 5)
				&& (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Office Protocol Discovery") !== false)
			)
		)
		{
			$digest = false;
		}
		else
		{
			$digest = true;
		}

		return $digest;
	}

	static public function GetWindowsVersion()
	{
		static $MODULE = 'dav';
		static $PARAM = 'windows_version';
		static $savedValues = null;

		$result = '';
		$userIP = self::GetIP();
		if (empty($userIP))
			return $result;

		if ($savedValues === null)
		{
			$savedValues = @unserialize(COption::GetOptionString($MODULE, $PARAM, ''));
			if (!is_array($savedValues))
				$savedValues = array();
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('#Windows NT (\d{1})#', $ua, $matches) > 0)
		{
			$result = (int) $matches[1];
			if ($result > 0)
			{
				if (
					! isset($savedValues[$userIP])
					|| ($result !== (int) $savedValues[$userIP])
				)
				{
					$savedValues[$userIP] = $result;
					COption::SetOptionString($MODULE,$PARAM,serialize($savedValues));
				}
			}
		}
		else // seems to be webdav request, try to get os from history
		{
			if (isset($savedValues[$userIP]))
				$result = (int) $savedValues[$userIP];
		}

		return $result;
	}

	static function GetIP()
	{
		$result = "";

		if (getenv("HTTP_CLIENT_IP")
			&& strtolower(getenv("HTTP_CLIENT_IP")) !== "unknown")
				$result = getenv("HTTP_CLIENT_IP");

		elseif (getenv("HTTP_X_FORWARDED_FOR")
			&& strtolower(getenv("HTTP_X_FORWARDED_FOR")) !==  "unknown")
			$result = getenv("HTTP_X_FORWARDED_FOR");

		elseif (getenv("REMOTE_ADDR"
			&& strtolower(getenv("REMOTE_ADDR")) !==  "unknown"))
			$result = getenv("REMOTE_ADDR");

		elseif (!empty($_SERVER['REMOTE_ADDR'])
			&& strtolower($_SERVER['REMOTE_ADDR']) !==  "unknown")
			$result = $_SERVER['REMOTE_ADDR'];

		return $result;
	}

	function IsDavHeaders($params = "empty")
	{
		static $result = array();

		if ( ! isset($result[$params]))
			$result[$params] = self::_isDavHeaders($params);

		return $result[$params];
	}

	function _isDavHeaders($params = "empty")
	{
		$aDavHeaders = array(
			"DAV",
			"IF",
			"DEPTH",
			"OVERWRITE",
			"DESTINATION",
			"LOCK_TOKEN",
			"TIMEOUT",
			"STATUS_URI"
		);

		foreach ($aDavHeaders as $header)
		{
			if (array_key_exists("HTTP_".$header, $_SERVER))
			{
				return true;
			}
		}

		$aDavMethods = array(
			"PROPFIND",
			"PROPPATCH",
			"MKCOL",
			"COPY",
			"MOVE",
			"LOCK",
			"UNLOCK"
		);

		if ($params == "check_options"):
			$aDavMethods[] = "OPTIONS";
		elseif ($params == "check_all"):
			$aDavMethods[] = "OPTIONS";
			$aDavMethods[] = "HEAD";
			$aDavMethods[] = "PUT";
		endif;

		foreach ($aDavMethods as $method)
		{
			if ($_SERVER["REQUEST_METHOD"] == $method)
			{
				return true;
			}
		}

		if (strpos($_SERVER["HTTP_USER_AGENT"], "Microsoft Office") !== false &&
			strpos($_SERVER['HTTP_USER_AGENT'], "Outlook") === false)
		{
			return true;
		}

		return false;
	}

	public static function ProcessWebDavRequest()
	{
		$request = new CDavWebDavServerRequest($_SERVER);
		$webdav = new CDavWebDavServer($request);
		$webdav->ProcessRequest();
	}

	public static function ProcessRequest()
	{
		$request = new CDavRequest($_SERVER);
		$groupdav = new CDavGroupDav($request);
		$groupdav->ProcessRequest();
	}

	public static function GetCharset($siteId = null)
	{
		if (is_null($siteId))
			$siteId = SITE_ID;
		if (is_null($siteId) || empty($siteId))
			$siteId = CDav::GetIntranetSite();

		static $cs = null;
		if (is_null($cs))
		{
			$dbSite = CSite::GetByID($siteId);
			if ($arSite = $dbSite->Fetch())
				$cs = strtolower($arSite["CHARSET"]);
		}

		return $cs;
	}

	public static function GetTimezoneId($siteId = null)
	{
		if (is_null($siteId))
			$siteId = SITE_ID;

		static $tz = null;
		if (is_null($tz))
			$tz = COption::GetOptionString('dav', "timezone", "Europe/Moscow", $siteId);
		return $tz;
	}

	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	public static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				for ($i = 0, $n = count($arFieldsKeys); $i < $n; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $filter_keys[$i];
			$key_res = CDav::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				for ($j = 0, $n1 = count($vals); $j < $n1; $j++)
				{
					$val = $vals[$j];

					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
							);
						if ($arSqlSearch_tmp1 !== false)
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
									$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0, $n2 = count($arSqlSearch_tmp); $j < $n2; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		for ($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				if ($arFields[$by]["TYPE"] == "datetime" || $arFields[$by]["TYPE"] == "date")
					$arSqlOrder[] = " ".$by."_X1 ".$order." ";
				else
					$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}

	public static function CheckIfRightSlashAdded($str)
	{
		if (substr($str, -1) != '/')
			return $str."/";

		return $str;
	}

	public static function EndsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
			return true;
		return (substr($haystack, -$length) === $needle);
	}

	public static function FormatDateTime($date, $format = null)
	{
		// 20110118T131500
		if (strlen($date) < 15)
			return 0;

		$year = intval(substr($date, 0, 4));
		$month = intval(substr($date, 4, 2));
		$day = intval(substr($date, 6, 2));
		$hour = intval(substr($date, 9, 2));
		$minute = intval(substr($date, 11, 2));
		$second = intval(substr($date, 13, 2));

		$t = mktime($hour, $minute, $second, $month, $day, $year);

		if (!is_string($format) || empty($format))
			$format = FORMAT_DATETIME;

		return date($GLOBALS["DB"]->DateFormatToPHP($format), $t);
	}

	public static function PackPrivileges($arPrivileges)
	{
		static $arPrivilegesMap = array('read' => 1, 'write-properties' => 2, 'write-content' => 4, 'unlock' => 8, 'read-acl' => 16,
			'read-current-user-privilege-set' => 32, 'bind' => 64, 'unbind' => 128, 'write-acl' => 256, 'read-free-busy' => 512,
			'schedule-deliver-invite' => 1024, 'schedule-deliver-reply' => 2048, 'schedule-query-freebusy' => 4096,
			'schedule-send-invite' =>  8192, 'schedule-send-reply' => 16384, 'schedule-send-freebusy' => 32768,
			'write' => 198 /* 2 + 4 + 64 + 128 */, 'schedule-deliver' => 7168 /* 1024 + 2048 + 4096 */,
			'schedule-send' => 57344 /* 8192 + 16384 + 32768 */, 'all' => GW_MAXIMUM_PRIVILEGES);

		if (!is_array($arPrivileges))
			$arPrivileges = array($arPrivileges);

		$result = 0;
		foreach ($arPrivileges as $privilege)
		{
			$privilege = trim(strtolower(preg_replace('/^.*:/', '', $privilege)));

			if (array_key_exists($privilege, $arPrivilegesMap))
				$result |= $arPrivilegesMap[$privilege];
		}

		if (($result & GW_MAXIMUM_PRIVILEGES) >= GW_MAXIMUM_PRIVILEGES)
			$result = pow(2, 24) - 1;

		return $result;
	}

	public static function ToString($var)
	{
		switch (($type = gettype($var)))
		{
			case 'boolean':
				return $var ? 'true' : 'false';
			case 'string':
				return "'$var'";
			case 'integer':
			case 'double':
			case 'resource':
				return $var;
			case 'NULL':
				return 'null';
			case 'object':
			case 'array':
				return str_replace(array("\n", '    '), '', print_r($var, true));
		}
		return 'unknown';
	}

	public static function Report($place, $varName, $varValue = "UNDEFINED", $mark = false)
	{
		if (defined("GW_DEBUG") && GW_DEBUG === true)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/__dav_debug.txt", "a");
			if ($mark)
				fwrite($f, "--------------------------------------------------------------\n");
			fwrite($f, date("H:i:s")." ".$place.": ".$varName);
			if ($varValue !== "UNDEFINED")
			{
				if (is_array($varValue))
					fwrite($f, "\n\t".preg_replace("#\r?\n#i", "\n\t", print_r($varValue, true))."\n");
				else
					fwrite($f, " = ".$varValue."\n");
			}
			else
			{
				fwrite($f, "\n");
			}
			if ($mark)
				fwrite($f, "--------------------------------------------------------------\n");
			fclose($f);
		}
	}

	public static function WriteToLog($text, $code = "")
	{
		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav.log";

		if ($f = fopen($filename, "a"))
		{
			fwrite($f, date("Y-m-d H:i:s")." ".str_pad($code, 7)." ".$text."\n");
			fclose($f);
		}
	}

	public static function UseProxy()
	{
		$useProxy = COption::GetOptionString("dav", "use_proxy", "N");
		$proxyHost = COption::GetOptionString("dav", "proxy_host", "");
		return (($useProxy == "Y") && (strlen($proxyHost) > 0));
	}

	public static function GetProxySettings()
	{
		return array(
			"PROXY_SCHEME" => COption::GetOptionString("dav", "proxy_scheme", ""),
			"PROXY_HOST" => COption::GetOptionString("dav", "proxy_host", ""),
			"PROXY_PORT" => COption::GetOptionString("dav", "proxy_port", ""),
			"PROXY_USERNAME" => COption::GetOptionString("dav", "proxy_username", ""),
			"PROXY_PASSWORD" => COption::GetOptionString("dav", "proxy_password", "")
		);
	}

	public static function GetIntranetSite()
	{
		static $intranetSite = null;

		if (is_null($intranetSite))
		{
			$arSkipSites = array();
			if (IsModuleInstalled("extranet"))
				$arSkipSites[] = COption::GetOptionString("extranet", "extranet_site", "ex");

			$arSites = array();

			$dbSite = CSite::GetList($o = "SORT", $b = "ASC", array("ACTIVE" => "Y"));
			while ($arSite = $dbSite->Fetch())
			{
				if (!in_array($arSite["ID"], $arSkipSites))
					$arSites[] = $arSite["ID"];
			}

			if (count($arSites) > 0)
				$intranetSite = $arSites[0];
		}

		return $intranetSite;
	}
}
?>

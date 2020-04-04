<?
if (strpos($_SERVER['SCRIPT_NAME'], "/bitrix/groupdav.php") === 0)
	return;

if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND' || $_SERVER['REQUEST_METHOD'] == 'OPTIONS')
{
	if (preg_match("/(bitrix|coredav|iphone|davkit|dataaccess|sunbird|lightning|cfnetwork|zideone|webkit|khtml|ical4ol|ios\\/([5-9]|10|11|12)|mac\\sos|mac_os_x|carddavbitrix24|caldavbitrix24|mac\\+os\\+x)/i", $_SERVER['HTTP_USER_AGENT']))
	{
		CHTTP::SetStatus("302 Found");
		header('Location: /bitrix/groupdav.php/');
		die();
	}
}

if (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) != 'Y' || !CModule::includeModule('disk'))
	return;

if (!defined("STOP_WEBDAV") || !STOP_WEBDAV)
{
	if (!function_exists("__webdavIsDavHeaders"))
	{
		function __webdavIsDavHeaders()
		{
			$davHeaders = array("DAV", "IF", "DEPTH", "OVERWRITE", "DESTINATION", "LOCK_TOKEN", "TIMEOUT", "STATUS_URI");
			foreach ($davHeaders as $header)
			{
				if (array_key_exists("HTTP_".$header, $_SERVER))
					return true;
			}

			$davMethods = array("OPTIONS", "PUT", "PROPFIND", "PROPPATCH", "MKCOL", "COPY", "MOVE", "LOCK", "UNLOCK", "DELETE", "COPY", "MOVE");
			foreach ($davMethods as $method)
			{
				if ($_SERVER["REQUEST_METHOD"] == $method)
					return true;
			}

			if (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Office") !== false &&
				strpos($_SERVER['HTTP_USER_AGENT'], "Outlook") === false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "MiniRedir") !== false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "WebDAVFS") !== false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "davfs2") !== false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "Sardine") !== false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "gvfs") !== false
					||
				strpos($_SERVER['HTTP_USER_AGENT'], "LibreOffice") !== false
			)
			{
				return true;
			}

			return false;
		}
	}

	$bNeedInclude = true;
	if ($_SERVER["REQUEST_METHOD"] == "HEAD")
	{
		$res = strtolower($_SERVER["HTTP_USER_AGENT"]);
		if (strpos($res, "microsoft") === false &&
			strlen($_SERVER["REAL_FILE_PATH"]) <= 0 && substr($_SERVER['REQUEST_URI'], -1, 1) == '/')
		{
			$bNeedInclude = false;
			$res = CUrlRewriter::GetList(Array("QUERY" => $_SERVER['REQUEST_URI']));
			foreach ($res as $res_detail)
			{
				if (strpos($res_detail["ID"], "webdav") !== false || strpos($res_detail["ID"], "disk") !== false || strpos($res_detail["ID"], "socialnetwork") !== false)
				{
					$bNeedInclude = true;
					break;
				}
			}
		}
	}

	if (__webdavIsDavHeaders() && $bNeedInclude)
	{
		if (CModule::includeModule('ldap') && CLdapUtil::isBitrixVMAuthSupported())
		{
			CLdapUtil::bitrixVMAuthorize();
		}

		if (!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_USER'])
		{
			$res = (!empty($_SERVER['REDIRECT_REMOTE_USER']) ? $_SERVER['REDIRECT_REMOTE_USER'] : $_SERVER['REMOTE_USER']);
			if (!empty($res) && preg_match('/(?<=(basic\s))(.*)$/is', $res, $matches))
			{
				$res = trim($matches[0]);
				list($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]) = explode(':', base64_decode($res));
			}
		}

		if (!is_array($GLOBALS["APPLICATION"]->arComponentMatch))
			$GLOBALS["APPLICATION"]->arComponentMatch = array();

		$GLOBALS["APPLICATION"]->arComponentMatch[] = 'dav';
		$GLOBALS["APPLICATION"]->arComponentMatch[] = 'disk';
		$GLOBALS["APPLICATION"]->arComponentMatch[] = 'socialnetwork';

		define("STOP_STATISTICS", true);
		define("NO_AGENT_STATISTIC","Y");
		define("NO_AGENT_CHECK", true);
		$GLOBALS["APPLICATION"]->ShowPanel = false;

		if (CModule::IncludeModule("dav") && CModule::IncludeModule("disk"))
		{
			//CDav::OnBeforePrologWebDav();
			CDav::Report(
				"<<<<<<<<<<<<<< REQUEST >>>>>>>>>>>>>>>>",
				"\n".print_r(array("REQUEST_METHOD" => $_SERVER["REQUEST_METHOD"], "REQUEST_URI" => $_SERVER["REQUEST_URI"], "PATH_INFO" => $_SERVER["PATH_INFO"], "HTTP_DEPTH" => $_SERVER["HTTP_DEPTH"], "AUTH_TYPE" => $_SERVER["AUTH_TYPE"], "PHP_AUTH_USER" => $_SERVER["PHP_AUTH_USER"]), true)."\n",
				"UNDEFINED",
				true
			);

			CDav::ProcessWebDavRequest();   //OnBeforePrologWebDav();
			die();
		}
	}
}

$app = $GLOBALS["USER"]->GetParam("APPLICATION_ID");
if ($app == "caldav" || $app == "carddav" || $app == "webdav")
	die();

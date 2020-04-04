<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::includeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!IsModuleInstalled("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
endif;
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["PERMISSION"] = strToUpper(trim($arParams["PERMISSION"]));
	if (strlen($arParams["BASE_URL"])>0)
	{
		$arParams["BASE_URL"] = trim($arParams["BASE_URL"]);
	}
	else
	{
		$arParams["BASE_URL"] = $arParams["OBJECT"]->base_url;
		$arParams['BASE_URL'] = str_replace(":443", "", rtrim($arParams['BASE_URL'], '/'));
		$arParams["BASE_URL"] = ($APPLICATION->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$arParams['BASE_URL']."/");
	}
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array("help" => "help");

	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action", "edit_section"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arResult["URL"] = array(
		"HELP" => CComponentEngine::MakePathFromTemplate($arParams["HELP_URL"], array())
	);
/********************************************************************
				Diagnostics
********************************************************************/

	if (!function_exists('get_response_code'))
	{
		function get_response_code($in)
		{
			$line = substr($in, 0, strpos($in, "\n"));
			preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|',$line,$match); 
			if (!empty($match[1]) && intval($match[1])>0)
				return intval($match[1]);
			else
				return 500;
		}
	}

	if (!function_exists('do_http_request'))
	{
		function do_http_request($method,  $path, $hostname=null, $hostport=null, $agent="Mozilla/5.0 (X11; U; Linux i686; ru; rv:1.9.2.12) Gecko/20101027 Ubuntu/10.04 (lucid) Firefox/3.6.12")
		{
			static $disabled = null;

			if ($disabled === null)
			{
				$disabled_functions = ini_get('disable_functions');
				$disabled = ! (strpos($disabled_functions, 'fsockopen') === false);
			}

			if ($disabled)
				return false;

			if ($hostname == null)
				$hostname = $_SERVER['SERVER_NAME'];
			if ($hostport == null)
				$hostport = $_SERVER['SERVER_PORT'];
			if (empty($hostport) || intval($hostport) <= 0) 
				$hostport = 80;
			$fp = fsockopen((($hostport == 443) ? 'ssl://' : '').$hostname, $hostport, $errno, $errstr, 60);
			if (!$fp) {
				//echo "$errstr ($errno)<br />\n";
				return false;
			} else {
				$out = "$method $path HTTP/1.1\r\n";
				$out .= "Host: $hostname:$hostport\r\n";
				$out .= "User-Agent: $agent\r\n";
				$out .= "Referer: http://$hostname:$hostport$path\r\n";
				$out .= "Accept-Encoding: deflate\r\n";
				$out .= "Connection: Close\r\n";
				$out .= "\r\n";
				fwrite($fp, $out);
				$in = '';
				while (!feof($fp)) {
					$in.=fgets($fp, 1024);
				}
				fclose($fp);
			}
			return $in;
		}
	}

	if (!IsModuleInstalled('bitrix24'))
	{
		if ($in = do_http_request('OPTIONS', '/docs/shared/'))
		{
			$aResponse = explode("\r\n", $in);
			foreach ($aResponse as $sLine)
			{
				if (!empty($sLine))
				{
					list($paramName, $paramVal) = explode(':', $sLine);
					$hResponse[$paramName] = trim($paramVal);
				}
			}

			if (strpos($hResponse['Server'], "Apache") === false)
			{
				//"Apache do not recive DAV protocol requests. Check the front-end server, if any.";
			}
			if (!(isset($hResponse['X-Powered-CMS']) && (strpos($hResponse['X-Powered-CMS'], 'Bitrix Site Manager') !== false) ))
			{
				if (isset($hResponse['MS-Author-Via']))
				{
					//"Apache modules mod_dav and mod_dav_fs should be disabled.";
				} else {
				}
			}

			if (get_response_code($in) === 401) // request for authentification
			{
				if (strpos($hResponse['WWW-Authenticate'], 'NTLM') !== false)
					$serverParams['AUTH_MODE'] = 'NTLM';
				elseif (strpos($hResponse['WWW-Authenticate'], 'Basic') !== false)
					$serverParams['AUTH_MODE'] = 'BASIC';
				elseif (strpos($hResponse['WWW-Authenticate'], 'Digest') !== false)
					$serverParams['AUTH_MODE'] = 'DIGEST';
				else
					$serverParams['AUTH_MODE'] = 'NONE';
			} else 
				$serverParams['AUTH_MODE'] = 'NONE';

			$serverParams['SECURE'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443);

		} else {
			//"Failed to perform Web Server Configuration Test for Bitrix WebDAV.";
		}

		if ($in = do_http_request('OPTIONS', '/docs/shared', null, null, "Microsoft Data Access Internet Publishing Provider"))
			if (get_response_code($in) !== 301) "Apache 'BrowserMatch \"Microsoft Data Access Internet Publishing Provider\" redirect-carefully' parameter should be disabled. Windows XP users may experience problems working by WebDAV protocol otherwise.";

		if ($in = do_http_request('OPTIONS', '/docs/shared', null, null, "Microsoft-WebDAV-MiniRedir"))
			if (get_response_code($in) !== 301) "Apache 'BrowserMatch \"Microsoft-WebDAV-MiniRedir\" redirect-carefully' parameter should be disabled. Microsoft Windows users may experience problems working by WebDAV protocol otherwise.";
	}
	else
	{
		$serverParams['AUTH_MODE'] = 'DIGEST';
		$serverParams['SECURE'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443);
	}

	// end of admin part
	// user part

	/*$client = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match("/(msie) ([0-9]{1,2}.[0-9]{1,3})/i", $client, $match)) {
		$browser['name'] = "MSIE";
		$browser['version'] = $match[2];
	}
	if (preg_match("/linux/i", $client))
		$serverParams['CLIENT_OS'] = "Linux";
	elseif (preg_match("/(windows nt)( ){0,1}([0-9]{1,2}.[0-9]{1,2}){0,1}/i", $client, $match))
	{
		if (isset($match[3]))
		{
			if ($match[3] == '5.0') $serverParams['CLIENT_OS'] = "Windows 2000";
			elseif ($match[3] == '5.1') $serverParams['CLIENT_OS'] = "Windows XP";
			elseif ($match[3] == '5.2') $serverParams['CLIENT_OS'] = "Windows 2003";
			elseif ($match[3] == '6.0' && strpos($client, 'SLCC1') !== false) $serverParams['CLIENT_OS'] = "Windows Vista";
			elseif ($match[3] == '6.0' && strpos($client, 'SLCC2') !== false) $serverParams['CLIENT_OS'] = "Windows 2008";
			elseif ($match[3] == '6.0') $serverParams['CLIENT_OS'] = "Windows Vista"; // may be 2008
			elseif ($match[3] == '6.1') $serverParams['CLIENT_OS'] = "Windows 7";
			else $serverParams['CLIENT_OS'] = "Windows";
		} else {
			$serverParams['CLIENT_OS'] = "Windows";
		}
	}
	elseif (preg_match("/mac/i", $client))
		$serverParams['CLIENT_OS'] = "Mac";
	*/
	$clientOS = CWebDavBase::GetClientOS();
	if($clientOS !== null)
	{
		$serverParams['CLIENT_OS'] = $clientOS;
	}

	$arResult['serverParams'] = $serverParams;


/********************************************************************
				/Diagnostics
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_TITLE"));
}
if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());

$this->IncludeComponentTemplate();
?>

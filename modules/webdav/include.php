<?
CModule::IncludeModule("iblock");
//IncludeModuleLangFile(__FILE__);

$GLOBALS["WEBDAV"] = array(
	"FORBIDDEN_SYMBOLS" => array(
		"/", "\\", ":", "*", "?", "\"", "'", "<", ">", "|", "#", "{", "}", "%", "&", "~", "+"),
	"ALLOWED_SYMBOLS" => array(
		"#", "+"));
$res = array();
foreach ($GLOBALS["WEBDAV"]["FORBIDDEN_SYMBOLS"] as $symbol)
{
	if (!in_array($symbol, $GLOBALS["WEBDAV"]["ALLOWED_SYMBOLS"]))
		$res[] = $symbol;
}
$GLOBALS["WEBDAV"]["FORBIDDEN_SYMBOLS_PATTERN"] = '/[\\'.implode("\\", $res).']/';
$GLOBALS["WEBDAV"]["FORBIDDEN_SYMBOLS_STRING"] = implode("", $res);
$GLOBALS["WEBDAV"]["CACHE"] = array();

$targetpath = preg_replace("'[\\\\/]+'", "/", $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/tmp/webdav/");
if (is_dir($targetpath) && is_writeable($targetpath))
{
	$GLOBALS["WEBDAV"]["PATH"] = $targetpath;
}
else
{
	$GLOBALS["WEBDAV"]["PATH"] = '';
	$path = preg_replace("'[\\\\/]+'", "/", $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/");
	if (is_dir($path) && is_writeable($path))
	{
		$path = preg_replace("'[\\\\/]+'", "/", $path."/tmp/");
		if (!is_dir($path)) // not exist, create
			mkdir($path);
		if (is_dir($path) && is_writeable($path))
		{
			$path = preg_replace("'[\\\\/]+'", "/", $path."/webdav/");
			if (!is_dir($path)) // not exist, create
				mkdir($path);
			if (is_dir($path) && is_writeable($path))
			{
				$GLOBALS["WEBDAV"]["PATH"] = $path;
			}
		}
	}
	else
	{
		$path = "/tmp/";
		if (is_dir($path) && is_writeable($path))
		{
			$GLOBALS["WEBDAV"]["PATH"] = $path;
		}
	}
}
global $DBType;
CModule::AddAutoloadClasses(
	"webdav",
	array(
		"CWebDavBase" => "classes/general.php",
		"__CParseLockinfo" => "classes/general.php",
		"__CParsePropfind" => "classes/general.php",
		"__CParseProppatch" => "classes/general.php",
		"CWebDavIblock" => "classes/iblock.php",
		"CWebDavFile" => "classes/file.php",
		"CDBResultWebDAVFiles" => "classes/file.php",
		"CWebDavVirtual" => "classes/virtual.php",
		"CIBlockDocumentWebdav" => "classes/iblockbizproc.php",
		"CBPWebDavCanUserOperateOperation" => "classes/iblockbizproc.php",
		"CIBlockWebdavSocnet" => "classes/iblocksocnet.php",
		"CIBlockDocumentWebdavSocnet" => "classes/iblocksocnetbizproc.php",
		"CWebDavEventLog" => "classes/event_log.php",
		"CEventWebDav" => "classes/event_log.php",
		"CRatingsComponentsWebDav" => "classes/ratings_components.php",
		"CWebdavDocumentHistory" => "classes/iblockbizprochistory.php",
		"CWebDavSocNetEvent" => "classes/iblocksocnetevent.php",
		"CWebDavInterface" => "classes/interface.php",
		"CUserTypeWebdavElement" => "classes/usertypewebdav.php",
		"CUserTypeWebdavElementHistory" => "classes/usertypewebdavelementhistory.php",

		"CWebDavExtLinks" => "classes/extlinks.php",
		"CWebDavDiskDispatcher" => "classes/diskdispatcher.php",
		"CWebDavAccessDeniedException" => "classes/diskdispatcher.php",
		"CWebDavAbstractStorage" => "classes/abstractstorage.php",
		"CWebDavStorageCore" => "classes/webdavstorage.php",
		"CDiskStorage" => "classes/diskstorage.php",
		"CWebDavTmpFile" => "classes/webdavtmpfile.php",
		"CWebDavTools" => "classes/tools.php",
		"CWebDavLogDeletedElementBase" => "classes/general/webdavlogdeletedelement.php",
		"CWebDavLogDeletedElement" => "classes/".$DBType."/webdavlogdeletedelement.php",
		"CWebDavEditDocBase" => "classes/editdocbase.php",
		"CWebDavEditDocComponentBase" => "classes/editdoccomponentbase.php",
		"CWebDavEditDocGoogle" => "classes/editdocgoogle.php",
		"CWebDavEditSkyDrive" => "classes/editskydrive.php",
		"CWebDavLogOnlineEditBase" => "classes/general/webdavonlineedit.php",
		"CWebDavLogOnlineEdit" => "classes/".$DBType."/webdavonlineedit.php",
		"CWebDavBlankDocument" => "classes/blankdocument.php",
		"CWebDavStubTmpFile" => "classes/stubwebdavtmpfile.php",
		"CWebDavSymlinkHelper" => "classes/symlinkhelper.php",
	));

CJSCore::RegisterExt('wdfiledialog', array(
	'js' => '/bitrix/js/webdav/file_dialog.js',
	'css' => '/bitrix/js/webdav/css/file_dialog.css',
	'lang' => '/bitrix/modules/webdav/lang/'.LANGUAGE_ID.'/install/js/file_dialog.php',
	'rel' => array('core', 'popup', 'json', 'ajax')
));

define("SONET_SUBSCRIBE_ENTITY_FILES", "F");
define("ENTITY_FILES_SOCNET_EVENT_ID", "files");
define("ENTITY_FILES_COMMON_EVENT_ID", "commondocs");
define("ENTITY_FILES_SOCNET_COMMENTS_EVENT_ID", "files_comment");
define("ENTITY_FILES_COMMON_COMMENTS_EVENT_ID", "commondocs_comment");

function WDBpCheckEntity($str = "")
{
	return in_array($str, array("CIBlockWebdavSocnet", "CIBlockDocumentWebdavSocnet"));
}
function WDAddPageParams($page_url="", $params=array(), $htmlSpecialChars = true)
{
	$strUrl = "";
	$strParams = "";
	$arParams = array();
	$param = "";
	// Attention: $page_url already is safe.
	if (is_array($params) && (count($params) > 0))
	{
		foreach ($params as $key => $val)
		{
			if ((is_array($val) && (count($val) > 0)) || ((strLen($val)>0) && ($val!="0")) || (intVal($val) > 0))
			{
				if (is_array($val))
					$param = implode(",", $val);
				else
					$param = $val;
				if (strLen($param) > 0)
				{
					if (strPos($page_url, $key) !== false)
					{
						$page_url = preg_replace("/".$key."\=[^\&]*((\&amp\;)|(\&)*)/", "", $page_url);
					}
					$arParams[] = $key."=".$param;
				}
			}
		}

		if (count($arParams) > 0)
		{
			if (strPos($page_url, "?") === false)
				$strParams = "?";
			elseif ((substr($page_url, -5, 5) != "&amp;") && (substr($page_url, -1, 1) != "&") && (substr($page_url, -1, 1) != "?"))
			{
				$strParams = "&";
			}
			$strParams .= implode("&", $arParams);
			if ($htmlSpecialChars)
				$page_url .= htmlspecialcharsbx($strParams);
			else
				$page_url .= $strParams;
		}
	}
	return $page_url;
}

function WDShowError($arError, $bShowErrorCode = false)
{
	$bShowErrorCode = ($bShowErrorCode === true ? true : false);
	$sReturn = "";
	$tmp = false;
	$arRes = array();
	if (empty($arError))
		return $sReturn;

	if (!is_array($arError))
	{
		$sReturn = $arError;
	}
	else
	{
		$arRes = array();
		foreach ($arError as $res)
		{
			$sReturn = $res["title"];

			if (empty($sReturn) || $bShowErrorCode)
				$sReturn .= " [CODE: ".$res["code"]."]";
			$arRes[] = $sReturn;
		}
		$sReturn = implode("<br />", $arRes);
	}
	return $sReturn;
}

function WDClearComponentCache($components)
{
	if (empty($components))
		return false;
	if (is_array($components))
		$aComponents = $components;
	else
		$aComponents = explode(",", $components);
	foreach($aComponents as $component_name)
	{
		$component_name = "bitrix:".$component_name;
		$componentRelativePath = CComponentEngine::MakeComponentPath($component_name);
		if (strlen($componentRelativePath) > 0)
		{
			$arComponentDescription = CComponentUtil::GetComponentDescr($component_name);
			if (isset($arComponentDescription) && is_array($arComponentDescription))
			{
				if (array_key_exists("CACHE_PATH", $arComponentDescription))
				{
					if($arComponentDescription["CACHE_PATH"] == "Y")
						$arComponentDescription["CACHE_PATH"] = "/".SITE_ID.$componentRelativePath;
					if(strlen($arComponentDescription["CACHE_PATH"]) > 0)
						BXClearCache(true, $arComponentDescription["CACHE_PATH"]);
				}
			}
		}
	}
}

function WDGetCookieID()
{
	static $sCookieID = "";
	if (empty($sCookieID))
	{
		$arId = array(
			"REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'],
			"USER_ID" => $GLOBALS["USER"]->GetId(),
			"USER_AGENT" => $_SERVER['HTTP_USER_AGENT']);
		$sCookieID = md5(serialize($arId));
	}
	return $sCookieID;
}

function WDPackCookie()
{
	if (empty($_COOKIE))
	{
		$id = WDGetCookieID();
		if (empty($_SESSION["WEBDAV_DATA"]) || !is_array($_SESSION["WEBDAV_DATA"]))
		{
			WDCleanPackedCookie($id);
		}
		else
		{
			CheckDirPath($GLOBALS["WEBDAV"]["PATH"]);
			file_put_contents($GLOBALS["WEBDAV"]["PATH"]."cookie".$id, serialize($_SESSION["WEBDAV_DATA"]));
			$_SESSION["WEBDAV_DATA_PACKED"] = "Y";
		}
	}
}

function WDCleanPackedCookie($id = "")
{
	$id = trim($id);

	if (!empty($id))
	{
		if (is_file($GLOBALS["WEBDAV"]["PATH"]."cookie".$id))
			@unlink($GLOBALS["WEBDAV"]["PATH"]."cookie".$id);
	}
	elseif ($handler = opendir($GLOBALS["WEBDAV"]["PATH"]))
	{
		$time = time();
		while (($file = readdir($handler)) !== false)
		{
			if ($file == "." || $file == "..")
				continue;
			$file_path = $GLOBALS["WEBDAV"]["PATH"].$file;
			$file_time = $time - filemtime($file_path);
			if ($file_time > 86400)
			{
				@unlink($file_path);
			}
		}
	}
}

function WDUnpackCookie()
{
	static $b = false;
	if ($b) { return false; }
	$b = true;

	if (empty($_COOKIE))
	{
		$id = WDGetCookieID();
		if (is_file($GLOBALS["WEBDAV"]["PATH"]."cookie".$id))
		{
			$res = file_get_contents($GLOBALS["WEBDAV"]["PATH"]."cookie".$id);
			if (!empty($res))
				$_SESSION["WEBDAV_DATA"] = @unserialize($res);
		}
	}
	elseif ($_SESSION["WEBDAV_DATA_PACKED"] == "Y")
	{
		$id = WDGetCookieID();
		WDCleanPackedCookie($id);
		unset($_SESSION["WEBDAV_DATA_PACKED"]);
	}
	$_SESSION["WEBDAV_DATA"] = (is_array($_SESSION["WEBDAV_DATA"]) ? $_SESSION["WEBDAV_DATA"] : array());
}
/* @deprecated */
function WDGetComponentsOnPage($filesrc = false)
{
	static $cache = array();
	if (!array_key_exists($filesrc, $cache))
	{
		$text = ''; $arResult = array();
		if ($filesrc !== false)
		{
			$io = CBXVirtualIo::GetInstance();
			$filesrc = $io->CombinePath("/", $filesrc);
			$filesrc = CSite::GetSiteDocRoot(SITE_ID).$filesrc;
			$f = $io->GetFile($filesrc);
			$text = $f->GetContents();
		}
		if ($text != '')
		{
			$arPHP = PHPParser::ParseFile($text);

			foreach ($arPHP as $php)
			{
				$src = $php[2];
				if (stripos($src, '$APPLICATION->IncludeComponent(') !== false)
					$arResult[] = PHPParser::CheckForComponent2($src);
			}
		}
		$cache[$filesrc] = $arResult;
	}
	return $cache[$filesrc];
}


class CWebDavUpdater
{
	function Run($version)
	{
		$r = include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webdav/updtr".$version.".php");
		if ($r === false)
			return "CWebDavUpdater::Run('".EscapePHPString($version)."');";
		else
			return "";
	}
}

class CWebdavUpdateAgent
{
	function Run()
	{
		$version = "1002";
		include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/webdav/updtr".$version.".php");
		$ag = new CWebdavUpdateAgent1002();
		if ($ag->Run())
			return "CWebdavUpdateAgent::Run();";
		else
			return;
	}
}
?>

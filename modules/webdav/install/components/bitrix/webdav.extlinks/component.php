<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
{
	if(preg_match('%([0-9a-f]{32})%iUu', $_SERVER['REQUEST_URI'], $m))
	{
		$resF = null;
		if(CModule::IncludeModule('webdav'))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$resF = CWebDavExtLinks::GetList(array(
				'HASH' => $m[0],
				'ACTUAL' => true,
				'RESOURCE_TYPE' => 'FOLDER',
				'LINK_TYPE' => CWebDavExtLinks::LINK_TYPE_MANUAL
			), array(
				'URL',
				'RESOURCE_TYPE',
				'FOLDER',
				'IBLOCK_TYPE',
				'IBLOCK_ID',
				'BASE_URL',
				'HASH',
				'CREATION_DATE',
				'USER_ID',
				'SALT',
				'PASSWORD',
				'LIFETIME',
				'F_SIZE',
				'DESCRIPTION',
				'ROOT_SECTION_ID',
				'URL_HASH',
				'SINGLE_SESSION',
				'LINK_TYPE',
				'DOWNLOAD_COUNT',
				'VERSION_ID',
				'ELEMENT_ID',
				'FILE_ID',
			))->fetch();
		}
		if(!$resF)
		{
			$forwardUrl = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
				'hash' => $m[0],
				'action' => empty($_REQUEST['LoadFile']) ? 'default' : 'download',
			));
			LocalRedirect($forwardUrl);
		}
	}
}

function CheckUserPassword($arF)
{
	
	if(!array_key_exists("PASSWORD", $arF) || strlen($arF["PASSWORD"]) <= 0)
	{
		return "NOT";
	}
	$res = "PASSWORD";
	$pass = "";
	if(array_key_exists("USER_PASSWORD", $_REQUEST) && strlen($_REQUEST["USER_PASSWORD"]) > 0)
	{
		$pass = $_REQUEST["USER_PASSWORD"];
		$res = "PASSWORD_WRONG";
	}
	elseif(isset($_SESSION["WEBDAV_DATA"]["EXT_LINK_PASSWORD"]) && strlen($_SESSION["WEBDAV_DATA"]["EXT_LINK_PASSWORD"]) > 0)
	{
		$pass = $_SESSION["WEBDAV_DATA"]["EXT_LINK_PASSWORD"];
	}
	
	if(CWebDavExtLinks::CheckPassword($arF, $pass))
	{
		if(!array_key_exists("WEBDAV_DATA",$_SESSION))
		{
			$_SESSION["WEBDAV_DATA"] = array();
		}
		$_SESSION["WEBDAV_DATA"]["EXT_LINK_PASSWORD"] = $pass;
		return "NOT";
	}
	return $res;
}

if (!CModule::IncludeModule("webdav"))
{
	ShowError(GetMessage("WD_MODULE_IS_NOT_INSTALLED"));
	return 0;
}

$sType = "cp";
if(IsModuleInstalled("bitrix24"))
{
	$sType = "b24";
}
elseif(SITE_TEMPLATE_ID == "bitrix24")
{
	$sType = "b24_template";
}

$arResult = array( 
	"SITE_TYPE" => $sType,
	"ICON" => "empty.jpg",
	"F_SIZE" => 0,
	"DESCRIPTION" => "",
	"FILE_NOT_FOUND" => false,
	"PASSWORD" => "NOT", // "NOT", "PASSWORD", "PASSWORD_WRONG"
);



$hash = CWebDavExtLinks::GetHashFromURL();
if($hash === false)
{
	ShowError(GetMessage('WD_MODULE_IS_FILE_NOT_FOUND'));
	return 0;
}
//not set default value to in getList LINK_TYPE
$resF = CWebDavExtLinks::GetList(array("HASH" => $hash, "ACTUAL" => true, 'LINK_TYPE' => null), array(
		"URL",
		"RESOURCE_TYPE",
		"FOLDER",
		"IBLOCK_TYPE",
		"IBLOCK_ID",
		"BASE_URL",
		"HASH",
		"CREATION_DATE",
		"USER_ID",
		"SALT",
		"PASSWORD",
		"LIFETIME",
		"F_SIZE",
		"DESCRIPTION",
		"ROOT_SECTION_ID",
		"URL_HASH",
		'SINGLE_SESSION',
		'LINK_TYPE',
		'DOWNLOAD_COUNT',
		'VERSION_ID',
		'ELEMENT_ID',
		'FILE_ID',
	));
$arGetListRes = null;
if($arF = $resF->Fetch())
{
	$arGetListRes = $arF;
	$arResult["HASH"] = $hash;
	$arResult["NAME"] = GetFileName($arF["URL"]);		
	$arResult["ICON"] = CWebDavExtLinks::GetExtensionIcon($arF["URL"]);
	$arResult["F_SIZE"] = $arF["F_SIZE"];
	$arResult["DESCRIPTION"] = $arF["DESCRIPTION"];
	$arResult["PASSWORD"] = CheckUserPassword($arF);
	$arResult["DOWNLOAD_COUNT"] = $arF['DOWNLOAD_COUNT'];
}

if(!$arF || !empty($_GET['notfoud']))
{
	$arResult["NAME"] =  GetMessage("WD_MODULE_IS_FILE_NOT_FOUND");		
	$arResult["ICON"] = "nf.png";
	$arResult["F_SIZE"] = 0;
	$arResult["DESCRIPTION"] = GetMessage("WD_MODULE_IS_FILE_NOT_FOUND_DESCRIPTION");
	$arResult["FILE_NOT_FOUND"] = true;
}

if(!empty($_POST['checkViewByGoogle']))
{
	CWebDavTools::sendJsonResponse(array(
		'viewByGoogle' => $arResult["DOWNLOAD_COUNT"] > 0,
	));
}

if(!empty($arF) && !empty($arF['LINK_TYPE']) && $arF['LINK_TYPE'] == CWebDavExtLinks::LINK_TYPE_AUTO)
{
	CWebDavExtLinks::LoadFile($arGetListRes);
}

if(!empty($arF) && !empty($arF['SINGLE_SESSION']))
{
	CWebDavExtLinks::DeleteSingleSessionLink($hash);
	CWebDavExtLinks::LoadFile($arGetListRes);
}

$arResult["COMPANY_NAME"] = COption::GetOptionString("main", "site_name", "");
if(
	array_key_exists("LoadFile", $_REQUEST)
	&& intval($_REQUEST["LoadFile"]) > 0
	&& $arResult["PASSWORD"] == "NOT"
	&& $arGetListRes['LINK_TYPE'] != CWebDavExtLinks::LINK_TYPE_AUTO
)
{
	CWebDavExtLinks::LoadFile($arGetListRes);
}

$arResult['ALLOW_VIEWER'] = false;
if($arResult["PASSWORD"] == "NOT" && empty($arF['PASSWORD']) && CWebDavExtLinks::DEMO_HASH != $hash)
{
	$allowExtDocServicesGlobal = CWebDavTools::allowUseExtServiceGlobal();

	if($allowExtDocServicesGlobal && CWebDavTools::allowPreviewFile($arF["URL"], $arResult["F_SIZE"]))
	{
		$arResult['ALLOW_VIEWER'] = true;
	}
}

$APPLICATION->RestartBuffer();
$this->IncludeComponentTemplate();


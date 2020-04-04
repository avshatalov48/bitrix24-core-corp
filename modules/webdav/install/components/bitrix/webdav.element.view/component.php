<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
endif;
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
	}
	$ob = $arParams["OBJECT"]; 
	
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["ROOT_SECTION_ID"] = intVal($arParams["ROOT_SECTION_ID"]);
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["CHECK_CREATOR"] = ($arParams["CHECK_CREATOR"] == "Y" ? "Y" : "N");
	$arParams["MERGE_VIEW"] = ($arParams["MERGE_VIEW"] == "Y" ? "Y" : "N");
	$arParams["ELEMENT_ID"] = intVal(!empty($arParams["ELEMENT_ID"]) ? $arParams["ELEMENT_ID"] : $_REQUEST["ELEMENT_ID"]);
	$arParams["REPLACE_SYMBOLS"] = ($arParams["REPLACE_SYMBOLS"] == "Y" ? "Y" : "N");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#",
		
		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_history" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#",
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#",
		"element_versions" => "PAGE_NAME=element_version&ELEMENT_ID=#ELEMENT_ID#",
		
		"help" => "PAGE_NAME=help",
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $APPLICATION->GetCurPage()."?".$URL_VALUE;
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") === false);
	if (!$arParams["CONVERT_PATH"])
		$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#"));
	$arParams["CONVERT_PATH"] = (strToLower($arParams["CONVERT"]) == "full" ? true : $arParams["CONVERT_PATH"]);
/***************** ADDITIONAL **************************************/
	$arParams["WORKFLOW"] = $ob->workflow; 
	$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
		
	$arParams["SET_STATUS_404"] = ($arParams["SET_STATUS_404"] == "Y" ? "Y" : "N");
	$arParams["USE_WORKFLOW"] = ($ob->workflow == "workflow" ? "Y" : "N"); 
	$arParams["USE_BIZPROC"] = ($ob->workflow == "bizproc" ? "Y" : "N"); 
	if (!empty($arParams["BIZPROC"]) && ($ob->workflow == "bizproc" || $ob->workflow == "bizproc_limited"))
	{
		$arParams["BIZPROC"] = array(
			"MODULE_ID" => "webdav", 
			"ENTITY" => (!WDBpCheckEntity($arParams["BIZPROC"]["ENTITY"]) ? "CIBlockDocumentWebdav" : $arParams["BIZPROC"]["ENTITY"]), 
			"DOCUMENT_TYPE" => (empty($arParams["BIZPROC"]["DOCUMENT_TYPE"]) ? "iblock_".$arParams["IBLOCK_ID"] : $arParams["BIZPROC"]["DOCUMENT_TYPE"]));
		$ob->wfParams["DOCUMENT_TYPE"] = array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["BIZPROC"]["DOCUMENT_TYPE"]); 
	}
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["DOCUMENT_ID"][2] = $arParams["ELEMENT_ID"]; 

	$arParams["NAME_FILE_PROPERTY"] = $ob->file_prop; 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/

if ($ob->permission < "R")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}

$db_res = $ob->_get_mixed_list(null, $arParams, $arParams["ELEMENT_ID"]); 
if (!($db_res && $arResult["ELEMENT"] = $db_res->GetNext()))
{
	$db_res = $ob->_get_mixed_list(null, $arParams += array("SHOW_VERSION" => "Y"), $arParams["ELEMENT_ID"]); 
	if (!($db_res && $arResult["ELEMENT"] = $db_res->GetNext()))
	{
		ShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
		if ($arParams["SET_STATUS_404"] == "Y"):
			CHTTP::SetStatus("404 Not Found");
		endif;
		return 0;
	}
}
if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
{
	/** @var \Bitrix\Disk\File $diskFile */
	$diskFile = \Bitrix\Disk\File::load(array('XML_ID' => $arResult['ELEMENT']['ID']), array('STORAGE'));
	if($diskFile)
	{
		LocalRedirect(\Bitrix\Disk\Driver::getInstance()->getUrlManager()->getPathFileDetail($diskFile));
	}
}
$ob->_get_file_info_arr($arResult["ELEMENT"]); 
__prepare_item_info($arResult["ELEMENT"], $arParams); 
$arResult["ELEMENT"]['URL']['THIS'] = $ob->_uencode($arResult["ELEMENT"]['URL']['THIS'], array("utf8" => "Y", "convert" => $arParams["CONVERT"])); 

if ($ob->workflow == "bizproc" && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && $arResult["ELEMENT"]["PERMISSION"] < "R")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
/********************************************************************
				Data
********************************************************************/
/************** Element ********************************************/
$arResult["ELEMENT"]["EXTENTION"] = $arResult["ELEMENT"]["FILE_EXTENTION"];
$arResult["ELEMENT"]["NAME_CONVERTED"] = CWebDavIblock::_uencode($arResult["ELEMENT"]["~NAME"], array("utf8" => "Y", "convert" => "allowed"));
/************** Parent element *************************************/
if ($arParams["WORKFLOW"] != "workflow" && $arParams["PERMISSION"] >= "U" && intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) > 0 && 
	$arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] != $arParams["ELEMENT_ID"])
{
	$db_res = CIBlockElement::GetList(array(), array("ID" => $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"], "SHOW_NEW" => "Y"));
	if ($db_res && $obElement = $db_res->GetNextElement())
		$arResult["ELEMENT"]["ORIGINAL"] = $obElement->GetFields() + array("PROPERTIES" => $obElement->GetProperties());
	$ob->_get_file_info_arr($arResult["ELEMENT"]["ORIGINAL"]); 
	__prepare_item_info($arResult["ELEMENT"]["ORIGINAL"], $arParams); 
}
/************** Sections info **************************************/
$arResult["ROOT_SECTION"] = $ob->arRootSection; 
$arParams["SECTION_ID"] = ($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $arParams["ROOT_SECTION_ID"] ? 0 : $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]);
/************** Another info ***************************************/
$arResult["USERS"] = array(
	$arResult["ELEMENT"]["~MODIFIED_BY"] => $arResult["ELEMENT"]["MODIFIED_BY"], 
	$arResult["ELEMENT"]["~CREATED_BY"] => $arResult["ELEMENT"]["CREATED_BY"], 
	$arResult["ELEMENT"]["~WF_LOCKED_BY"] => $arResult["ELEMENT"]["WF_LOCKED_BY"]);
$arResult["ELEMENT"]["MODIFIED_BY"] = $arResult["ELEMENT"]["~MODIFIED_BY"]; 
$arResult["ELEMENT"]["CREATED_BY"] = $arResult["ELEMENT"]["~CREATED_BY"]; 
$arResult["ELEMENT"]["WF_LOCKED_BY"] = $arResult["ELEMENT"]["~WF_LOCKED_BY"];
/************** Paths **********************************************/
$arResult["URL"] = $arResult["ELEMENT"]["URL"] + array(
	"OPEN" => $arResult["ELEMENT"]["URL"]["THIS"], 
	"DOWNLOAD_ORIGINAL" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_GET_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["ORIGINAL"]["ID"], "ID" => $arResult["ELEMENT"]["ORIGINAL"]["ID"], 
			"ELEMENT_NAME" => $arResult["ELEMENT"]["ORIGINAL"]["NAME"])), 
	"VIEW_ORIGINAL" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["ORIGINAL"]["ID"], "ELEMENT_NAME" => $arResult["ELEMENT"]["ORIGINAL"]["NAME"])), 
			array("action" => "view_original")), 
	"DOWNLOAD_LAST" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_GET_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ID" => $arResult["ELEMENT"]["LAST_ID"], "ELEMENT_NAME" => $arResult["ELEMENT"]["NAME"])), 
	"VIEW_LAST" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ELEMENT_NAME" => $arResult["ELEMENT"]["NAME"])), 
	"EDIT_LAST" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ELEMENT_NAME" => $arResult["ELEMENT"]["NAME"], "ACTION" => "EDIT")), 
	"DELETE_LAST" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ACTION" => "DELETE")), 
				array("edit" => "y", "sessid" => bitrix_sessid())), 
	"LOCK_LAST" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ACTION" => "LOCK")), array("edit" => "y", "sessid" => bitrix_sessid())), 
	"UNLOCK_LAST" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["LAST_ID"], "ACTION" => "UNLOCK")), 
				array("edit" => "y", "sessid" => bitrix_sessid())));
/************** View mode ******************************************/
$arParams["VIEW_MODE"] = "CURRENT";
if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U")
{
	$arParams["VIEW_MODE"] = ($arResult["ELEMENT"]["REAL_ID"] != $arResult["ELEMENT"]["LAST_ID"] ? "HISTORY" : 
		($_REQUEST["action"] == "view_original" ? "ORIGINAL" : $arParams["VIEW_MODE"]));
}
/********************************************************************
				/Data
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$title = GetMessage("WD_TITLE")." ".$arResult["ELEMENT"]["NAME"];
	if ($arParams["VIEW_MODE"] == "HISTORY") 
		$title = GetMessage("WD_TITLE_1").$arResult["ELEMENT"]["ORIGINAL"]["NAME"];
	elseif ($arParams["VIEW_MODE"] == "ORIGINAL")
		$title = GetMessage("WD_TITLE_2").$arResult["ELEMENT"]["NAME"];
	$APPLICATION->SetTitle($title);
}

if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	$res = array("section_id" => (!empty($arResult["ELEMENT"]["ORIGINAL"]) ? $arResult["ELEMENT"]["ORIGINAL"]["IBLOCK_SECTION_ID"] : $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])); 
	$arResult["NAV_CHAIN"] = $ob->GetNavChain($res, "array");
	
	$arNavChain = array(); 
	foreach ($arResult["NAV_CHAIN"] as $res)
	{
		$arNavChain[] = $res["URL"];
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array("PATH" => implode("/", $arNavChain), "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files"));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res["NAME"]), $url);
	}
	if (!empty($arResult["ELEMENT"]["ORIGINAL"])) 
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"], array("ELEMENT_ID" => $arResult["ELEMENT"]["ORIGINAL"]["ID"]));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx(GetMessage("WD_ORIGINAL").$arResult["ELEMENT"]["ORIGINAL"]["~NAME"]), $url);
	}
	$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($arResult["ELEMENT"]["~NAME"]));
}
if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/

if ($arParams["WORKFLOW"] == "workflow" && (!empty($arResult["ELEMENT"]["ORIGINAL"]) && intVal($arResult["ELEMENT"]["ORIGINAL"]["WF_STATUS_ID"]) > 1 || 
	empty($arResult["ELEMENT"]["ORIGINAL"]) && intVal($arResult["ELEMENT"]["WF_STATUS_ID"]) > 1))
{
	return array("ELEMENT_ID" => false);
}
elseif (!empty($arResult["ELEMENT"]["ORIGINAL"]))
{
	return array(
		"ELEMENT_ID" => $arResult["ELEMENT"]["ORIGINAL"]["ID"], 
		"ELEMENT" => $arResult["ELEMENT"]);
}
else
{
	return array(
		"ELEMENT_ID" => $arParams["ELEMENT_ID"], 
		"ELEMENT" => $arResult["ELEMENT"]);
}
?>

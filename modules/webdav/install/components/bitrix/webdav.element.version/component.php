<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = "IBLOCK"; 
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
		$arParams["OBJECT"]->IsDir(array("element_id" => $arParams["ELEMENT_ID"])); 
	}
	$ob = $arParams["OBJECT"]; 
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["ROOT_SECTION_ID"] = ($ob->arRootSection ? $ob->arRootSection["ID"] : false);
	$arParams["ELEMENT_ID"] = intVal($arParams["ELEMENT_ID"]);
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["CHECK_CREATOR"] = ($arParams["CHECK_CREATOR"] == "Y" ? "Y" : "N");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_history" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#",
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#", 
		
		"webdav_bizproc_view" => "PAGE_NAME=webdav_bizproc_view&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_start_bizproc" => "PAGE_NAME=webdav_start_bizproc&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_task_list" => "PAGE_NAME=webdav_task_list", 
		"webdav_task" => "PAGE_NAME=webdav_task&ID=#ID#");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") === false);
	if (!$arParams["CONVERT_PATH"])
		$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#"));
	$arParams["CONVERT_PATH"] = (strToLower($arParams["CONVERT"]) == "full" ? true : $arParams["CONVERT_PATH"]);
/***************** ADDITIONAL **************************************/
	$arParams["WORKFLOW"] = (!$ob->workflow ? "N" : $ob->workflow); 
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["DOCUMENT_ID"][2] = $arParams["ELEMENT_ID"]; 
	$arParams["GRID_ID"] = "WebDAVVersions".$arParams["IBLOCK_ID"]."_".$arParams["ELEMENT_ID"]; 
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
if ($arParams["WORKFLOW"] == "bizproc" && (!CBPDocument::CanUserOperateDocument(
		CBPCanUserOperateOperation::ReadDocument, 
		$GLOBALS["USER"]->GetID(),
		$arParams["DOCUMENT_ID"],
		array(
			"DocumentType" => $arParams["DOCUMENT_TYPE"], 
			"IBlockPermission" => $arParams["PERMISSION"]))))
{
	$arParams["PERMISSION"] = "D"; 
}
unset($file);
if ($arParams["PERMISSION"] < "U")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
elseif (!$ob->PROPFIND(
	$options = array("element_id" => $arParams["ELEMENT_ID"]), 
	$file, 
	array("COLUMNS" => $arSelectedFields, "get_clones" => "N", "return" => "array")))
{
	ShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
	return 0;
}
elseif ($arParams["CHECK_CREATOR"] == "Y")
{
	$res = reset($file);
	if ($res["CREATED_BY"] != $GLOBALS['USER']->GetId()):
		ShowError(GetMessage("WD_ACCESS_DENIED"));
		return 0;
	endif;
}

/********************************************************************
				ACTIONS
********************************************************************/
$GLOBALS["APPLICATION"]->ResetException();
$arResult["ERROR_MESSAGE"] = ""; 
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php");
$result = include($path);
if ($result !== true):
	$oError = $GLOBALS["APPLICATION"]->GetException();
	if ($oError):
		$arResult["ERROR_MESSAGE"] = $oError->GetString();
	endif;
endif;
/********************************************************************
				/ACTIONS
********************************************************************/

/********************************************************************
				Default params
********************************************************************/
$cache = new CPHPCache;
$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/".$arParams["IBLOCK_ID"]."/");
$arResult["ELEMENT"] = reset($file["files"]);
__prepare_item_info($arResult["ELEMENT"], $arParams); 
$arResult["VERSIONS"] = array(); 
$arParams["TEMPLATES"] = array(); 
if ($arParams["WORKFLOW"] == "bizproc")
{
	$arParams["BIZPROC_START"] = false;
	$arTemplates = array();
	if ($arParams["PERMISSION"] >= "U")
	{
		$cache_id = "bizproc_templates";
		$cache_path = $cache_path_main."bizproc";
		if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
		{
			$arTemplates = $cache->GetVars();
		}
		else
		{
			$db_res = CBPWorkflowTemplateLoader::GetList(
				array(),
				array("DOCUMENT_TYPE" => $arParams["DOCUMENT_TYPE"]),
				false,
				false,
				array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS", "TEMPLATE")
			);
			while ($arWorkflowTemplate = $db_res->GetNext())
			{
				$arTemplates[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
			}
			if ($arParams["CACHE_TIME"] > 0):
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
				$cache->EndDataCache($arTemplates);
			endif;
		}
	}
	$arParams["TEMPLATES"] = $arTemplates; 
}
$arResult["URL"] = array(
	"~CLONE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
		array("PATH" => $arResult["ELEMENT"]["PATH"], "ELEMENT_ID" => $arResult["ELEMENT"]["ID"], "ACTION" => "CLONE")), 
	"CLONE" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
		array("PATH" => $arResult["ELEMENT"]["PATH"], "ELEMENT_ID" => $arResult["ELEMENT"]["ID"], "ACTION" => "CLONE"))); 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/************** Versions *******************************************/
$arResult["NAV_RESULT"] = $db_res = $ob->_get_mixed_list($arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
	array("SHOW_VERSIONS" => "Y"), $arResult["ELEMENT"]["ID"]);
if ($arResult["NAV_RESULT"])
{
	$arFilter = array(
		"IBLOCK_ID" => $ob->IBLOCK_ID, 
		"NAME" => $ob->meta_names['TRASH']['name']
	);
	$arSelectedFields = array("ID");
	$db_res = CIBlockSection::GetMixedList(array(), $arFilter, false, $arSelectedFields);
	if ($db_res && ($arTrash = $db_res->GetNext()))
		$trashID = $arTrash["ID"];
	if ($arParams["PAGE_ELEMENTS"] > 0)
	{
		$arResult["NAV_RESULT"]->NavStart($arParams["PAGE_ELEMENTS"], false); 
		$arResult["NAV_STRING"] = $arResult["NAV_RESULT"]->GetPageNavStringEx($navComponentObject, GetMessage("WD_DOCUMENTS"), $arParams["PAGE_NAVIGATION_TEMPLATE"], true);
	}
	while ($res = $arResult["NAV_RESULT"]->Fetch())
	{
		if (isset($trashID) && $res["IBLOCK_SECTION_ID"] == $trashID)
			continue;
		$ob->_get_file_info_arr($res); 
		__prepare_item_info($res, $arParams); 

		$rs = __build_item_info($res, $arParams); 
		$rs["columns"]["COMMENTS"] = ($arParams["WORKFLOW"] == "bizproc" ? $rs["columns"]["BIZPROC"] : 
			($arParams["WORKFLOW"] == "workflow" ? $rs["columns"]["WF_COMMENTS"] : "")); 

		$editable = true; 

		$arResult["VERSIONS"][] = array(
			"id" => $res["ID"], 
			"data" => $res, 
			"actions" => array_values($rs["actions"]), 
			"columns" => $rs["columns"], 
			"editable" => $editable);
	}
}

/********************************************************************
				/Data
********************************************************************/

/********************************************************************
				Action
********************************************************************/
if ((!empty($_POST["HISTORY_ID"]) || !empty($_GET["history_id"])) && check_bitrix_sessid())
{

}
/********************************************************************
				/Action
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_EV_TITLE")." ". $arResult["ELEMENT"]["NAME"]);
}

if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
?>

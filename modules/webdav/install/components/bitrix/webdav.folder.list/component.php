<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");

global $by, $order;
if (!$by)
{
	$by = $arParams["SORT_BY"];
	$order = $arParams["SORT_ORD"];
}
InitSorting();

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = "FOLDER"; 
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavFile($arParams, $arParams['BASE_URL']);
		$arParams["OBJECT"]->IsDir(); 
	}
	$ob = $arParams["OBJECT"]; 

	$arParams["FOLDER"] = $ob->real_path; 
	$arParams["~SECTION_ID"] = $arParams["SECTION_ID"] = $ob->arParams["item_id"];
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["SORT_BY"] = (!empty($arParams["SORT_BY"]) ? $arParams["SORT_BY"] : "NAME");
	$arParams["SORT_ORD"] = ($arParams["SORT_ORD"] != "DESC" ? "ASC" : "DESC");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "#PATH#",
		"section_edit" => "folder/#ACTION#/edit/#PATH#",
		
		"element_edit" => "element/#ACTION#/edit/#PATH#",
		"element_download" => "element/historyget/#PATH#",
		
		"help" => "help");

	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action", "edit_section", "result"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["ELEMENT_HISTORY_GET_URL"] = $arParams["ELEMENT_DOWNLOAD_URL"]; 
	$arParams["~ELEMENT_HISTORY_GET_URL"] = $arParams["~ELEMENT_DOWNLOAD_URL"]; 
/***************** ADDITIONAL **************************************/
	$arParams["DEFAULT_EDIT"] = ($arParams["DEFAULT_EDIT"] == "N" ? "N" : "Y");
	$arParams["GRID_ID"] = "WebDAV_".md5($arParams["FOLDER"]); 
	$arParams["COLUMNS"] = (is_array($arParams["COLUMNS"]) ? $arParams["COLUMNS"] : array()); 
	$arParams["COLUMNS"] = array_intersect(array("NAME", "TIMESTAMP_X", "FILE_SIZE"), $arParams["COLUMNS"]);
	$arParams["PAGE_ELEMENTS"] = intVal(intVal($arParams["PAGE_ELEMENTS"]) > 0 ? $arParams["PAGE_ELEMENTS"] : 50);
	$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
	$arParams["BASE_URL"] = $ob->base_url_full; 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
/********************************************************************
				/Input params
********************************************************************/

if ($arParams["PERMISSION"] < "R")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
elseif (isset($ob->meta_state) && ($arParams["PERMISSION"] < $ob->meta_names[$ob->meta_state]['rights']))
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}

/********************************************************************
				Default params
********************************************************************/
	$arResult["DATA"] = array();
	$arResult["GRID_DATA"] = array(); 
	$arResult["SECTION"] = $ob->arParams["dir_array"];
	$arResult["ERROR_MESSAGE"] = "";
	$arResult["NAV_CHAIN"] = $ob->GetNavChain();
	$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), true);
	
	$arNavChain = $arResult["NAV_CHAIN"]; $sCurrentFolder = array_pop($arNavChain);
	$arUrlParams = array('PATH' => implode("/", $arResult["NAV_CHAIN"]));
	$arResult["URL"] = array(
		"UP" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array('PATH' => implode("/", $arNavChain))),
		"UPLOAD" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_UPLOAD_URL"], $arUrlParams),
		"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], $arUrlParams),
		"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], $arUrlParams), 
		"HELP" => CComponentEngine::MakePathFromTemplate($arParams["HELP_URL"], array()));
	$arResult["SECTION_LIST"] = array(); 
	
	$arResult["FILTER"] = array();
	$arResult["FILTER"][] = array("id" => "content", "name" => GetMessage("WD_TITLE_CONTENT"), "default" => true, "type" => "search");

/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				ACTIONS
********************************************************************/
$GLOBALS["APPLICATION"]->ResetException();
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
				FILTER
********************************************************************/
$grid_options = new CGridOptions($arParams["GRID_ID"]);
$arFilter = array();
if (isset($_REQUEST["clear_filter"]) && $_REQUEST["clear_filter"] == "Y")
{
	$urlParams = array();
	foreach($arResult["FILTER"] as $id => $arFilter)
	{
		$urlParams[] = $arFilter["id"];
	}
	$urlParams[] = "clear_filter";
	$grid_filter = $grid_options->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam("", $urlParams));
}

$grid_filter = $grid_options->GetFilter($arResult["FILTER"]);
$arResult["FILTER_VALUE"] = $grid_filter;
/********************************************************************
				/FILTER
********************************************************************/
/********************************************************************
				Data
********************************************************************/
$tmp = $ob->PROPFIND(
	$options = array("path" => $ob->_path, "depth" => 1, "FILTER" => $grid_filter), 
	$files, 
	array("COLUMNS" => $arSelectedFields, "return" => "nav_result")); 
if (is_array($tmp))
	$arResult = array_merge($arResult, $tmp);

if ($arResult["NAV_RESULT"])
{
	if ($arParams["PAGE_ELEMENTS"] > 0)
	{
		$arResult["NAV_RESULT"]->NavStart($arParams["PAGE_ELEMENTS"], false); 
		$arResult["NAV_STRING"] = $arResult["NAV_RESULT"]->GetPageNavStringEx(
			$navComponentObject, 
			GetMessage("WD_DOCUMENTS"), 
			$arParams["PAGE_NAVIGATION_TEMPLATE"], 
			true);
	}
	
	while ($res = $arResult["NAV_RESULT"]->GetNext())
	{
		if (isset($res["~NAME"]) && $res["~NAME"] === $ob->meta_names['TRASH']['name'])
			continue;

		$res["TYPE"] = ($res["~TYPE"] == "FILE" ? "E" : "S"); 
		
		$res["~PATH"] = $res["PATH"]; 
		$res["PATH"] = $ob->_uencode($res["~PATH"], array("utf8" => "Y", "convert" => $arParams["CONVERT"])); 
		__prepare_item_info($res, $arParams); 

		$arResult["DATA"][$res["ID"]] = $res;
		
		$rs = __build_item_info($res, $arParams);
		unset($rs["actions"]['preview_launch']);

		$arResult["GRID_DATA"][] = array(
			"id" => $res["TYPE"].$res["ID"], 
			"data" => $res, 
			"actions" => array_values($rs["actions"]), 
			"columns" => $rs["columns"], 
			"editable" => (
				($arParams["PERMISSION"] >= "W")
				&& empty($arResult["FILTER_VALUE"])
			)
		);
	}
}
$arResult["GRID_DATA_COUNT"] = count($arResult["GRID_DATA"]);

if (!empty($sCurrentFolder))
{
	array_unshift(
		$arResult["GRID_DATA"], 
		array(
			"id" => "", 
			"data" => array(), 
			"actions" => false, 
			"columns" => array(
				"NAME" => '<div class="section-up"><div>'.
					'<a href="'.$arResult["URL"]["UP"].'"></a></div><a href="'.$arResult["URL"]["UP"].'">..</a></div>'), 
			"editable" => false));
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
	$title = (empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"]);
	$GLOBALS["APPLICATION"]->SetTitle(empty($sCurrentFolder) ? $title : $sCurrentFolder);
}
if ($arParams["SET_NAV_CHAIN"] == "Y" && !empty($sCurrentFolder))
{
	$res = array(); 
	foreach ($arNavChain as $name)
	{
		$res[] = $name; 
		$GLOBALS["APPLICATION"]->AddChainItem($name, 
			CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $res))));
	}
	$GLOBALS["APPLICATION"]->AddChainItem($sCurrentFolder, $arResult["URL"]["THIS"]);
}
/********************************************************************
				/Standart operations
********************************************************************/
?>

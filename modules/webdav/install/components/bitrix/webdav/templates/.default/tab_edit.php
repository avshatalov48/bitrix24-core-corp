<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
$arCurFileInfo = pathinfo(__FILE__);
$langfile = trim(preg_replace("'[\\\\/]+'", "/", ($arCurFileInfo['dirname']."/lang/".LANGUAGE_ID."/".$arCurFileInfo['basename'])));
__IncludeLang($langfile);

return $APPLICATION->IncludeComponent("bitrix:webdav.element.edit", "view_pro", Array(
    "IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
    "IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
    "SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
    "ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
    "REPLACE_SYMBOLS"	=>	$arParams["REPLACE_SYMBOLS"],
    "ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
    "CONVERT"	=>	$arParams["CONVERT"],
    "PERMISSION" => $arParams["PERMISSION"], 
    "CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
    "NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],

    "FORM_ID" => $arParams["FORM_ID"],
    "TAB_ID" => 'tab_main',
    
    "SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
    "SECTIONS_ALTERNATIVE_URL" => $arResult["URL_TEMPLATES"]["sections_short"],
    "ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
    "ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
    //"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
    "ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
    "ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
    "ELEMENT_VERSION_URL" => $arResult["URL_TEMPLATES"]["element_version"], 
    "ELEMENT_VERSIONS_URL" => $arResult["URL_TEMPLATES"]["element_versions"], 
	"ELEMENT_UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
    
    "USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"], 
    "WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"], 
    "WEBDAV_BIZPROC_LOG_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_log"], 
    "WEBDAV_START_BIZPROC_URL" => $arResult["URL_TEMPLATES"]["webdav_start_bizproc"], 
    "WEBDAV_TASK_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
    "WEBDAV_TASK_LIST_URL" => $arResult["URL_TEMPLATES"]["webdav_task_list"], 
    	
	 "SHOW_RATING" => $arParams["SHOW_RATING"],
	 "RATING_TYPE" => $arParams["RATING_TYPE"],
	
    "SET_TITLE"	=>	"Y",
    "SET_NAV_CHAIN"	=>	"Y",
    "STR_TITLE" => $arParams["STR_TITLE"], 
    "MERGE_VIEW" => "Y",
    "DOCUMENT_LOCK" => "N",
    "CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
    "CACHE_TIME"	=>	$arParams["CACHE_TIME"],
    "DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
    $component,
    array("HIDE_ICONS" => "Y")
);
?>

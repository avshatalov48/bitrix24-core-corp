<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$arInfo = $APPLICATION->IncludeComponent("bitrix:webdav.element.view", "", Array(
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"NAME_FILE_PROPERTY"	=>	$arParams["NAME_FILE_PROPERTY"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => "N",
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	//"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	
	"COLUMNS"	=>	$arParams["COLUMNS"],
	
	"SET_TITLE"	=>	"N",
	"STR_TITLE" => $arParams["STR_TITLE"], 
	"SET_NAV_CHAIN" => $arParams["SET_NAV_CHAIN"], 
	"SHOW_WEBDAV" => $arParams["SHOW_WEBDAV"], 
	
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<?
if(is_array($arInfo) && $arInfo["ELEMENT_ID"]):?>
<?$APPLICATION->IncludeComponent("bitrix:bizproc.document", "webdav.bizproc.document", Array(
	"MODULE_ID" => MODULE_ID,
	"ENTITY" => ENTITY,
	"DOCUMENT_TYPE" => DOCUMENT_TYPE,
	"DOCUMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
	
	"WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"],
	"TASK_EDIT_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
	"WORKFLOW_LOG_URL" => str_replace("#ELEMENT_ID#", "#DOCUMENT_ID#", $arResult["URL_TEMPLATES"]["webdav_bizproc_log"]), 
	"WORKFLOW_START_URL" => str_replace("#ELEMENT_ID#", "#DOCUMENT_ID#", $arResult["URL_TEMPLATES"]["webdav_start_bizproc"]), 
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	
	"SET_TITLE"	=>	"Y"),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<?
endif;
?>

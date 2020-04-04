<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.element.edit", "", Array(
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"REPLACE_SYMBOLS"	=>	$arParams["REPLACE_SYMBOLS"],
	"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
	"CONVERT"	=>	$arParams["CONVERT"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTIONS_ALTERNATIVE_URL" => $arResult["URL_TEMPLATES"]["sections_short"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	//"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"ELEMENT_VERSION_URL" => $arResult["URL_TEMPLATES"]["element_version"], 
	"ELEMENT_VERSIONS_URL" => $arResult["URL_TEMPLATES"]["element_versions"], 
	
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"], 
	"WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"], 
	"WEBDAV_BIZPROC_LOG_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_log"], 
	"WEBDAV_START_BIZPROC_URL" => $arResult["URL_TEMPLATES"]["webdav_start_bizproc"], 
	"WEBDAV_TASK_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
	"WEBDAV_TASK_LIST_URL" => $arResult["URL_TEMPLATES"]["webdav_task_list"], 
	
	"SHOW_RATING" => $arParams["SHOW_RATING"],
	"RATING_TYPE" => $arParams["RATING_TYPE"],
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"], 
    "MERGE_VIEW" => "N",
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);

?>

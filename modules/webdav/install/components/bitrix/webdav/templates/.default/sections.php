<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.section.list", ".default", Array(
	"OBJECT" => $arParams["OBJECT"], 
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"REPLACE_SYMBOLS"	=>	$arParams["REPLACE_SYMBOLS"],
	"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
	"CONVERT"	=>	$arParams["CONVERT"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTIONS_DIALOG" => $arResult["URL_TEMPLATES"]["sections_dialog"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	//"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"ELEMENT_VERSIONS_URL" => $arResult["URL_TEMPLATES"]["element_versions"], 
	"ELEMENT_UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
	"HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	"WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"], 
	"WEBDAV_START_BIZPROC_URL" => $arResult["URL_TEMPLATES"]["webdav_start_bizproc"], 
	"WEBDAV_TASK_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
	"WEBDAV_TASK_LIST_URL" => $arResult["URL_TEMPLATES"]["webdav_task_list"], 
		
	"SHOW_RATING" => $arParams["SHOW_RATING"],
	"RATING_TYPE" => $arParams["RATING_TYPE"],
	
	"COLUMNS"	=>	$arParams["COLUMNS"],
	"PAGE_ELEMENTS"	=>	$arParams["PAGE_ELEMENTS"],
	"PAGE_NAVIGATION_TEMPLATE"	=>	$arParams["PAGE_NAVIGATION_TEMPLATE"],
	"DEFAULT_EDIT" => $arParams["DEFAULT_EDIT"],
	"USE_COMMENTS" => $arParams["USE_COMMENTS"], 
	"FORUM_ID" => $arParams["FORUM_ID"], 
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"], 
	
	"SHOW_WORKFLOW"	=>	"Y"),
	$component,
	array("HIDE_ICONS" => "Y")
);?><?
if (!empty($arParams["SHOW_NOTE"])) 
{
?><p><?=$arParams["SHOW_NOTE"]?></p><?
}
?>

<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.folder.list", ".default", Array(
	"OBJECT" => $arParams["OBJECT"],
	"PERMISSION" => $arParams["PERMISSION"],
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],

	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	"ELEMENT_UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
	"ELEMENT_DOWNLOAD_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
	"DEFAULT_EDIT" => $arParams["DEFAULT_EDIT"],

	"COLUMNS"	=>	$arParams["COLUMNS"],
	"PAGE_ELEMENTS"	=>	$arParams["PAGE_ELEMENTS"],
	"PAGE_NAVIGATION_TEMPLATE"	=>	$arParams["PAGE_NAVIGATION_TEMPLATE"],

	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"],

	"SHOW_NOTE" => $arParams["SHOW_NOTE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>

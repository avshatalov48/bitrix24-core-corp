<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.element.hist", "", Array(
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"CONVERT"	=>	$arParams["CONVERT"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],

	"PAGE_ELEMENTS"	=>	$arParams["PAGE_ELEMENTS"],
	"PAGE_NAVIGATION_TEMPLATE"	=>	$arParams["PAGE_NAVIGATION_TEMPLATE"],
	
	"SET_TITLE"	=> $arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"],
	"NAME_TEMPLATE"	=>	$arParams["NAME_TEMPLATE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
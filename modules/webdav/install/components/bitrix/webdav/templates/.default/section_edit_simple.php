<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.section.edit", "popup", Array(
	"OBJECT" => $arParams["OBJECT"], 
	"SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
	"CONVERT"	=>	$arParams["CONVERT"],
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
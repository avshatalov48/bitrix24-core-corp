<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent(
	"bitrix:webdav.folder.edit", 
	"popup", 
	Array(
		"OBJECT" => $arParams["OBJECT"], 
		"SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
		"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
		
		"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
		"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
		
		"SET_TITLE"	=>	"Y",
		"STR_TITLE" => $arParams["STR_TITLE"],
		"SET_NAV_CHAIN" => "N",
		"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
		"CACHE_TIME"	=>	$arParams["CACHE_TIME"]),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
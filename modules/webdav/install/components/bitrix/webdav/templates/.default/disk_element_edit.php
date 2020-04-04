<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent(
	"bitrix:webdav.file.edit", 
	".default", 
	Array(
		"OBJECT" => $arParams["OBJECT"], 
		"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
		
		"UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
		"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
		"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
		
		
		"SET_TITLE"	=> $arParams["SET_TITLE"],
		"SET_NAV_CHAIN" => $arParams["SET_NAV_CHAIN"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>

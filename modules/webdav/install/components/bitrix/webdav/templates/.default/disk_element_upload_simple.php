<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.element.upload", "popup", Array(
	"OBJECT" => $arParams["OBJECT"], 
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
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"ELEMENT_UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
    "WEBDAV_BIZPROC_WORKFLOW_ADMIN" => $arResult['URL_TEMPLATES']['webdav_bizproc_workflow_admin'],
	
	"UPLOAD_MAX_FILE" => $arParams["UPLOAD_MAX_FILE"],
	"UPLOAD_MAX_FILESIZE" => $arParams["UPLOAD_MAX_FILESIZE"],
	
	"SET_TITLE"	=> $arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"],
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"], 
	
	"NOTE" => str_replace(
		"#HREF#", 
		$arResult["URL_TEMPLATES"]["help"], 
		GetMessage("WD_HOW_TO_INCREASE_QUOTA"))
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>


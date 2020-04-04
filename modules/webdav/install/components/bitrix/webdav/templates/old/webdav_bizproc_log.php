<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:bizproc.log", "webdav.bizproc.log", Array(
	"MODULE_ID" => MODULE_ID,
	"ENTITY" => ENTITY,
	"DOCUMENT_TYPE" => DOCUMENT_TYPE,
	"DOCUMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
	"ID" => $arResult["VARIABLES"]["ID"],
	"DOCUMENT_URL" => str_replace(
		array("#ELEMENT_ID#", "#ELEMENT_NAME#"), 
		array("#ID#", "#NAME#"), $arResult["URL_TEMPLATES"]["element_history_get"]),
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	"SET_TITLE"	=>	$arParams["SET_TITLE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
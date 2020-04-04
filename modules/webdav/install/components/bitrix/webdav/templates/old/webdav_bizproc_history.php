<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:bizproc.document.history", "", Array(
	"MODULE_ID" => MODULE_ID,
	"ENTITY" => ENTITY,
	"DOCUMENT_TYPE" => DOCUMENT_TYPE,
	"DOCUMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
	"DOCUMENT_URL" => str_replace(
		array("#ELEMENT_ID#", "#WORKFLOW_ID#", "#ELEMENT_NAME#"), 
		array($arResult["VARIABLES"]["ELEMENT_ID"], "#ID#", "#NAME#"), $arResult["URL_TEMPLATES"]["webdav_bizproc_history_get"]),
	"SET_TITLE"	=>	$arParams["SET_TITLE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
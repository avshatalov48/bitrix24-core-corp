<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.start", "", Array(
	"MODULE_ID" => MODULE_ID,
	"ENTITY" => ENTITY,
	"DOCUMENT_TYPE" => DOCUMENT_TYPE,
	"DOCUMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
	"TEMPLATE_ID" => $arResult["VARIABLES"]["TEMPLATE_ID"], 
	"SET_TITLE"	=>	$arParams["SET_TITLE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
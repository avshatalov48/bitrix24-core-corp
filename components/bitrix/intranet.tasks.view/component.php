<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("socialnetwork"))
	return ShowError(GetMessage("EC_SONET_MODULE_NOT_INSTALLED"));

$arResult = $arParams["arResult"];
$arResult["NAV_STRING"] = HtmlSpecialCharsBack($arResult["NAV_STRING"]);
$arParams = $arParams["arParams"];

$this->IncludeComponentTemplate();
?>
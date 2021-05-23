<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$gridId = $arParams["GRID_ID"];
$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $gridId);

$arResult["SELECTED_FIELDS"] = $arParams["SELECTED_FIELDS"];

if (isset($gridOptions["fields"]))
	$arResult["SELECTED_FIELDS"] = $gridOptions["fields"];

$arResult['ALL_FIELDS'] = $arParams["ALL_FIELDS"];
$arResult['EVENT_NAME'] = $arParams["EVENT_NAME"];

$this->IncludeComponentTemplate();
?>

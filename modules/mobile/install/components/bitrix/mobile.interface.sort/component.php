<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$gridId = $arParams["GRID_ID"];
$arResult["CURRENT_SORT_BY"] = "";
$arResult["CURRENT_SORT_ORDER"] = "asc";

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $gridId);
if (isset($gridOptions["sort_by"]))
	$arResult["CURRENT_SORT_BY"] = $gridOptions["sort_by"];

if (isset($gridOptions["sort_order"]) && in_array($gridOptions["sort_order"], array("asc", "desc")))
	$arResult["CURRENT_SORT_ORDER"] = $gridOptions["sort_order"];

$arResult['SORT_FIELDS'] = $arParams["SORT_FIELDS"];
$arResult['EVENT_NAME'] = $arParams["EVENT_NAME"];

$this->IncludeComponentTemplate();
?>

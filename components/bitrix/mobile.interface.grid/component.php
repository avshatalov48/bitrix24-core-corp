<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if(!isset($arParams["SORT_EVENT_NAME"]))
	$arParams["SORT_EVENT_NAME"] = "";

if(!isset($arParams["FIELDS_EVENT_NAME"]))
	$arParams["FIELDS_EVENT_NAME"] = "";

if(!isset($arParams["AJAX_PAGE_PATH"]))
	$arParams["AJAX_PAGE_PATH"] = "";

if(
	!isset($arParams["RELOAD_GRID_AFTER_EVENT"])
	|| !in_array($arParams["RELOAD_GRID_AFTER_EVENT"], array("Y", "N"))
)
	$arParams["RELOAD_GRID_AFTER_EVENT"] = "Y";

$arParams["GRID_ID"] = preg_replace("/[^a-z0-9_]/i", "", $arParams["GRID_ID"]);

if($arParams["NAV_STRING"] <> '')
{
	$arResult["NAV_STRING"] = $arParams["~NAV_STRING"];
}

if (isset($arParams["FIELDS"]) && is_array($arParams["FIELDS"]))
	$arResult["FIELDS"] = $arParams["FIELDS"];
else
	$arResult["FIELDS"] = array();

if (isset($arParams["ITEMS"]) && is_array($arParams["ITEMS"]))
	$arResult["ITEMS"] = $arParams["ITEMS"];
else
	$arResult["ITEMS"] = array();

if (isset($arParams["SECTIONS"]) && is_array($arParams["SECTIONS"]))
	$arResult["SECTIONS"] = $arParams["SECTIONS"];
else
	$arResult["SECTIONS"] = array();

$this->IncludeComponentTemplate();

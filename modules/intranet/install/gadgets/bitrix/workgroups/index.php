<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["GROUP_VAR"] = ($arGadgetParams["GROUP_VAR"]?$arGadgetParams["GROUP_VAR"]:"group_id");
$arGadgetParams["PATH_TO_GROUP"] = ($arGadgetParams["PATH_TO_GROUP"]?$arGadgetParams["PATH_TO_GROUP"]:"/workgroups/group/#group_id#/");
$arGadgetParams["PATH_TO_GROUP_SEARCH"] = ($arGadgetParams["PATH_TO_GROUP_SEARCH"]?$arGadgetParams["PATH_TO_GROUP_SEARCH"]:"/workgroups/");
$arGadgetParams["ITEMS_COUNT"] = ($arGadgetParams["ITEMS_COUNT"]?$arGadgetParams["ITEMS_COUNT"]:"4");


$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_top",
	"div",
	Array(
		"GROUP_VAR" => $arGadgetParams["GROUP_VAR"],
		"PATH_TO_GROUP" => $arGadgetParams["PATH_TO_GROUP"],
		"PATH_TO_GROUP_SEARCH" => $arGadgetParams["PATH_TO_GROUP_SEARCH"],
		"ITEMS_COUNT" => $arGadgetParams["ITEMS_COUNT"],
		"DATE_TIME_FORMAT" => $arGadgetParams["DATE_TIME_FORMAT"],
		"DISPLAY_PICTURE" => $arGadgetParams["DISPLAY_PICTURE"],
		"DISPLAY_DESCRIPTION" => $arGadgetParams["DISPLAY_DESCRIPTION"],
		"DISPLAY_NUMBER_OF_MEMBERS" => $arGadgetParams["DISPLAY_NUMBER_OF_MEMBERS"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
		"FILTER_MY" => $arGadgetParams["FILTER_MY"],
	),
	false,
	Array("HIDE_ICONS"=>"Y")

);?>

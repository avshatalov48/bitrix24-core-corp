<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$APPLICATION->IncludeComponent(
	"bitrix:bizproc.wizards.list",
	isset($arResult["COMPONENT_TEMPLATES"]["List"]) ? $arResult["COMPONENT_TEMPLATES"]["List"] : "",
	array(
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"TASK_VAR" => $arResult["ALIASES"]["task_id"],
		"BP_VAR" => $arResult["ALIASES"]["bp_id"],
		"BLOCK_VAR" => $arResult["ALIASES"]["block_id"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"BLOCK_ID" => $arResult["VARIABLES"]["block_id"],
		"PATH_TO_START" => $arResult["PATH_TO_START"],
		"PATH_TO_TASK" => $arResult["PATH_TO_TASK"],
		"PATH_TO_VIEW" => $arResult["PATH_TO_VIEW"],
		"PATH_TO_BP" => $arResult["PATH_TO_BP"],
		"PATH_TO_SETVAR" => $arResult["PATH_TO_SETVAR"],
		"PATH_TO_INDEX" => $arResult["PATH_TO_INDEX"],
		"PATH_TO_LOG" => $arResult["PATH_TO_LOG"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_NAV_CHAIN" => $arParams["SET_NAV_CHAIN"],
		"ITEMS_COUNT" => $arParams["ITEMS_COUNT"],
		"ADMIN_ACCESS" => $arParams["ADMIN_ACCESS"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	$component
);
?>
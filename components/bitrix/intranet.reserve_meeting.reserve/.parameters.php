<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("iblock"))
	return;

$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

$arUserGroups = array();
$dbGroups = CGroup::GetList($b = "NAME", $o = "ASC", array("ACTIVE" => "Y"));
while ($arGroup = $dbGroups->GetNext())
	$arUserGroups[$arGroup["ID"]] = "[".$arGroup["ID"]."] ".$arGroup["NAME"];

$arComponentParameters = Array(
	"GROUPS" => array(
		"VARIABLE_ALIASES" => array(
			"NAME" => GetMessage("INTL_VARIABLE_ALIASES"),
		),
	),
	"PARAMETERS" => Array(
		"IBLOCK_TYPE" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),
		"PAGE_VAR" => Array(
			"NAME" => GetMessage("INTL_PAGE_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"MEETING_VAR" => Array(
			"NAME" => GetMessage("INTL_MEETING_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"MEETING_ID" => Array(
			"NAME" => GetMessage("INTL_MEETING_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"ITEM_VAR" => Array(
			"NAME" => GetMessage("INTL_ITEM_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"ITEM_ID" => Array(
			"NAME" => GetMessage("INTL_ITEM_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"PATH_TO_MEETING" => Array(
			"NAME" => GetMessage("INTL_PATH_TO_MEETING"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_MEETING_LIST" => Array(
			"NAME" => GetMessage("INTL_PATH_TO_MEETING_LIST"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"SET_NAVCHAIN" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTL_SET_NAVCHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"
		),
		"SET_TITLE" => Array(),
		"USERGROUPS_RESERVE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_USERGROUPS_RESERVE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arUserGroups,
		),
	)
);
?>
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

$arTasksFields = array(
	"ID" => "[ID] ".GetMessage("INTL_TF_ID"),
	"NAME" => "[NAME] ".GetMessage("INTL_TF_NAME"),
	"CODE" => "[CODE] ".GetMessage("INTL_TF_CODE"),
	"XML_ID" => "[XML_ID] ".GetMessage("INTL_TF_XML_ID"),
	"MODIFIED_BY" => "[MODIFIED_BY] ".GetMessage("INTL_TF_MODIFIED_BY"),
	"DATE_CREATE" => "[DATE_CREATE] ".GetMessage("INTL_TF_DATE_CREATE"),
	"CREATED_BY" => "[CREATED_BY] ".GetMessage("INTL_TF_CREATED_BY"),
	"DATE_ACTIVE_FROM" => "[DATE_ACTIVE_FROM] ".GetMessage("INTL_TF_DATE_ACTIVE_FROM"),
	"DATE_ACTIVE_TO" => "[DATE_ACTIVE_TO] ".GetMessage("INTL_TF_DATE_ACTIVE_TO"),
	"IBLOCK_SECTION" => "[IBLOCK_SECTION] ".GetMessage("INTL_TF_IBLOCK_SECTION"),
	"DETAIL_TEXT" => "[DETAIL_TEXT] ".GetMessage("INTL_TF_DETAIL_TEXT"),
);

$dbTasksCustomProps = CIBlockProperty::GetList(
	array("sort" => "asc", "name" => "asc"),
	array("ACTIVE" => "Y", "IBLOCK_ID" => $arCurrentValues["IBLOCK_ID"])
);
while ($arTasksCustomProp = $dbTasksCustomProps->Fetch())
{
	$ind = ((StrLen($arTasksCustomProp["CODE"]) > 0) ? $arTasksCustomProp["CODE"] : $arTasksCustomProp["ID"]);
	$arTasksFields[StrToUpper($ind)] = "[".$ind."] ".$arTasksCustomProp["NAME"];
}

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
		"TASKS_FIELDS_SHOW" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_TASKS_FIELDS_SHOW"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arTasksFields,
		),
		"TASK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_TASK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array("group" => GetMessage("INTL_TASK_TYPE_GROUP"), "user" => GetMessage("INTL_TASK_TYPE_USER")),
		),
		"OWNER_ID" => Array(
			"NAME" => GetMessage("INTL_OWNER_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"TASK_VAR" => Array(
			"NAME" => GetMessage("INTL_TASK_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"USER_VAR" => Array(
			"NAME" => GetMessage("INTL_USER_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"GROUP_VAR" => Array(
			"NAME" => GetMessage("INTL_GROUP_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"VIEW_VAR" => Array(
			"NAME" => GetMessage("INTL_VIEW_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"ACTION_VAR" => Array(
			"NAME" => GetMessage("INTL_ACTION_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PAGE_VAR" => Array(
			"NAME" => GetMessage("INTL_PAGE_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PATH_TO_GROUP_TASKS" => Array(
			"NAME" => GetMessage("INTL_PATH_TO_GROUP_TASKS"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_TASKS_TASK" => Array(
			"NAME" => GetMessage("INTL_PATH_TO_GROUP_TASKS_TASK"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_TASKS_VIEW" => Array(
			"NAME" => GetMessage("INTL_PATH_TO_GROUP_TASKS_VIEW"),
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
		"ITEMS_COUNT" => Array(
			"NAME" => GetMessage("INTL_ITEM_COUNT"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "20",
			"COLS" => 3,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
	)
);
?>
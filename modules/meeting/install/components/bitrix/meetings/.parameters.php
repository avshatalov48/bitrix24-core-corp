<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("meeting")) return;

$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["RESERVE_MEETING_IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

if ($arCurrentValues["RESERVE_MEETING_IBLOCK_TYPE"] != $arCurrentValues["RESERVE_VMEETING_IBLOCK_TYPE"])
{
	$arIBlockV=array();
	$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["RESERVE_VMEETING_IBLOCK_TYPE"], "ACTIVE"=>"Y"));
	while($arr=$rsIBlock->Fetch())
		$arIBlockV[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}
else
{
	$arIBlockV = $arIBlock;
}

$arComponentParameters = array(
	"PARAMETERS" => array(
		"VARIABLE_ALIASES" => Array(
			"action" => Array("NAME" => "action"),
		),
		"SEF_MODE" => Array(
			"list" => array(
				"NAME" => GetMessage('M_PARAM_list'),
				"DEFAULT" => "",
				"VARIABLES" => array(),
			),
			"meeting" => array(
				"NAME" => GetMessage('M_PARAM_meeting'),
				"DEFAULT" => "meeting/#MEETING_ID#/",
				"VARIABLES" => array("MEETING_ID" => "MEETING_ID"),
			),
			"meeting_edit" => array(
				"NAME" => GetMessage('M_PARAM_meeting_edit'),
				"DEFAULT" => "meeting/#MEETING_ID#/edit/",
				"VARIABLES" => array("MEETING_ID" => "MEETING_ID"),
			),
			"meeting_copy" => array(
				"NAME" => GetMessage('M_PARAM_meeting_copy'),
				"DEFAULT" => "meeting/#MEETING_ID#/copy/",
				"VARIABLES" => array("MEETING_ID" => "MEETING_ID"),
			),
			"item" => array(
				"NAME" => GetMessage('M_PARAM_meeting_item'),
				"DEFAULT" => "item/#ITEM_ID#/",
				"VARIABLES" => array("ITEM_ID" => "ITEM_ID"),
			),
		),

		"RESERVE_MEETING_IBLOCK_TYPE" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),

		"RESERVE_MEETING_IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),

		"RESERVE_VMEETING_IBLOCK_TYPE" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK_TYPE_V"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),

		"RESERVE_VMEETING_IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_IBLOCK_V"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),
		
		"MEETINGS_COUNT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("M_MEETINGS_COUNT"),
			"TYPE" => "STRING",
			"DEFAULT" => "20"
		),

//		"CACHE_TIME" => array("DEFAULT" => "3600"),
	),
);
?>
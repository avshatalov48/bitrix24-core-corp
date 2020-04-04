<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("iblock"))
	return;

$bSocNet = CModule::IncludeModule("socialnetwork");

$arComponentParameters = array();
$arParams = array(); // $arComponentParameters["PARAMETERS"]

if ($bSocNet)
{
	$arParams["B_CUR_USER_LIST"] = Array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("ECL_P_CUR_USER_EVENT_LIST"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"REFRESH" => "Y",
	);
}

if ($arCurrentValues["B_CUR_USER_LIST"] != 'Y')
{
	$arIBlockType = array();
	$arIBlockTypeDef = false;
	$arIBlockDef = false;

	$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
	while ($arr=$rsIBlockType->Fetch())
	{
		if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		{
			$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
			if (strpos($arr["ID"], 'event') !== false || strpos($arr["ID"], 'calendar') !== false && $arIBlockTypeDef === false)
				$arIBlockTypeDef = $arr["ID"];
		}
	}

	$arIBlock=array();
	if ($arCurrentValues["IBLOCK_TYPE"] || $arIBlockTypeDef)
	{
		$val = isset($arCurrentValues["IBLOCK_TYPE"]) ? $arCurrentValues["IBLOCK_TYPE"] : $arIBlockTypeDef;
		$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $val, "ACTIVE"=>"Y"));
		while($arr=$rsIBlock->Fetch())
		{
			$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
			if ($arIBlockDef === false)
				$arIBlockDef = $arr["ID"];
		}
	}

	$arParams["IBLOCK_TYPE"] = Array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("ECL_P_IBLOCK_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlockType,
		"REFRESH" => "Y",
	);
	if ($arIBlockTypeDef !== false)
		$arParams["IBLOCK_TYPE"]['DEFAULT'] = $arIBlockTypeDef;

	$arParams["IBLOCK_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("ECL_P_IBLOCK"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlock,
		"REFRESH" => "Y",
	);
	if ($arIBlockDef !== false)
		$arParams["IBLOCK_ID"]['DEFAULT'] = $arIBlockDef;

	$arParams["IBLOCK_SECTION_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("ECL_P_IBLOCK_SECTION_ID"),
		"DEFAULT" => ""
	);
}

$arParams["INIT_DATE"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("ECL_P_INIT_DATE"),
	"DEFAULT" => '-'.GetMessage("ECL_P_SHOW_CUR_DATE").'-'
);

$arParams["FUTURE_MONTH_COUNT"] = array(
	"PARENT" => "BASE",
	"TYPE" => "LIST",
	"NAME" => GetMessage("ECL_P_FUTURE_MONTH_COUNT"),
	"VALUES" => Array("1" => "1","2" => "2","3" => "3","4" => "4","5" => "5","6" => "6","12" => "12","24" => "24"),
	"DEFAULT" => "2",
);

$arParams["DETAIL_URL"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("ECL_P_DETAIL_URL"),
	"DEFAULT" => ""
);

$arParams["EVENTS_COUNT"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("ECL_P_EVENTS_COUNT"),
	"DEFAULT" => "5"
);

$arParams["CACHE_TIME"] = Array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("ECL_P_CACHE_TIME"),
	"DEFAULT" => "3600",
);

$arComponentParameters["PARAMETERS"] = $arParams;
?>

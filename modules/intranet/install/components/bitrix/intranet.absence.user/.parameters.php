<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule('iblock');

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


$arComponentParameters = array(
	'PARAMETERS' => array(
		'ID' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => "={\$USER->GetID()}",
			'NAME' => GetMessage('INTR_IAU_PARAM_ID'),
			'PARENT' => 'BASE'
		),
		'CALENDAR_IBLOCK_TYPE' => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTR_ABSC_PARAM_CALENDAR_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		'CALENDAR_IBLOCK_ID' => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTR_ABSC_PARAM_CALENDAR_IBLOCK_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
		),
		
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);
?>
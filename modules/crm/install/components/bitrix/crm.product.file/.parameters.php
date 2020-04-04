<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("crm"))
	return;

$strSelectedType = $arCurrentValues["IBLOCK_TYPE_ID"];

$arTypes = array();
$rsTypes = CLists::GetIBlockTypes();
while($ar = $rsTypes->Fetch())
{
	$arTypes[$ar["IBLOCK_TYPE_ID"]] = "[".$ar["IBLOCK_TYPE_ID"]."] ".$ar["NAME"];
	if(!$strSelectedType)
		$strSelectedType = $ar["IBLOCK_TYPE_ID"];
}

$arIBlocks = array();
$rsIBlocks = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $strSelectedType, "ACTIVE"=>"Y"));
while($ar = $rsIBlocks->Fetch())
{
	$arIBlocks[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"CATALOG_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CRM_PRODUCT_FILE_CATALOG_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => '={$_REQUEST["list_id"]}',
		),
		"PRODUCT_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CRM_PRODUCT_FILE_PRODUCT_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["element_id"]}',
		),
		"FIELD_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CRM_PRODUCT_FILE_FIELD_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["field_id"]}',
		),
		"FILE_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CRM_PRODUCT_FILE_FILE_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["file_id"]}',
		),
	),
);
?>

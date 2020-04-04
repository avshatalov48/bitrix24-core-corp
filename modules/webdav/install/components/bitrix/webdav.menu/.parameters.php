<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(!CModule::IncludeModule("iblock"))
	return;

$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
	{
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
	}
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
{
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y"),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_IBLOCK_ID"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIBlock),
		"ROOT_SECTION_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_ROOT_SECTION_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => ''),
		"SECTION_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_SECTION_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["SECTION_ID"]}'),
		"ELEMENT_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_ELEMENT_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}'),
		"PAGE_NAME" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_PAGE_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["PAGE_NAME"]}'),
		"PERMISSION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_PERMISSION"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
/*		"CHECK_CREATOR"	=> array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_CHECK_CREATOR"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"), 
*/		"BASE_URL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_BASE_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),


		"SECTIONS_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_LIST_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=sections&PATH=#PATH#"),
		"SECTION_EDIT_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_EDIT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#"),
		"ELEMENT_EDIT_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_EDIT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#"),
		"ELEMENT_UPLOAD_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_UPLOAD_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_upload&SECTION_ID=#SECTION_ID#"),
		"STR_TITLE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_STR_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"USE_COMMENTS" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_USE_COMMENTS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"), 
		"FORUM_ID" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_FORUM_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => ""), 

		"CACHE_TIME"  =>  Array("DEFAULT"=>3600)
	)
);
?>
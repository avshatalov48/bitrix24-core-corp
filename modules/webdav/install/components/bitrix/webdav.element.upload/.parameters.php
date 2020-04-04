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
		"REPLACE_SYMBOLS" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_REPLACE_SYMBOLS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"),
		"NAME_FILE_PROPERTY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_NAME_FILE_PROPERTY"),
			"TYPE" => "STRING",
			"DEFAULT" => "FILE"),
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
*/			
			
		"SECTIONS_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_LIST_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=sections&PATH=#PATH#"),
		"ELEMENT_UPLOAD_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_UPLOAD_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_upload&SECTION_ID=#SECTION_ID#"),

		"UPLOAD_MAX_FILE" => array(
			"PARENT" => "ADDITIONAL",
			"NAME" => GetMessage("WD_UPLOAD_MAX_FILE"),
			"TYPE" => "STRING",
			"DEFAULT" => "3"),
		"UPLOAD_MAX_FILESIZE" => array(
			"PARENT" => "ADDITIONAL",
			"NAME" => str_replace("#upload_max_filesize#", ini_get('upload_max_filesize'), GetMessage("WD_UPLOAD_MAX_FILESIZE")),
			"TYPE" => "STRING",
			"DEFAULT" => ini_get('upload_max_filesize')),

		"SET_TITLE" => array(),
		"CACHE_TIME"  =>  Array("DEFAULT"=>3600),
		"DISPLAY_PANEL" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_PANEL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"),
	)
);
?>
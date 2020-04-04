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
		"ELEMENT_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_ELEMENT_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}'),
		"PERMISSION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_PERMISSION"),
			"TYPE" => "STRING",
			"DEFAULT" => ''), 
/*		"CHECK_CREATOR"	=> array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_CHECK_CREATOR"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"), 
*/		"ACTION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_ACTION"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["ACTION"]}'),
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

		"SECTIONS_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_LIST_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=sections&PATH=#PATH#"),
		"ELEMENT_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#"),
		"ELEMENT_EDIT_URL" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_EDIT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#"),
		"ELEMENT_HISTORY_GET" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_HISTORY_GET_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#"),
		"USER_VIEW" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_USER_VIEW_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=user_view&USER_ID=#USER_ID#"),
		"SHOW_RATING" => array(
			"NAME" => GetMessage("SHOW_RATING"),
			"TYPE" => "LIST",
			"VALUES" => Array(
				"" => GetMessage("SHOW_RATING_CONFIG"),
				"Y" => GetMessage("MAIN_YES"),
				"N" => GetMessage("MAIN_NO"),
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"RATING_TYPE" => Array(
			"NAME" => GetMessage("RATING_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => Array(
				"" => GetMessage("RATING_TYPE_CONFIG"),
				"like" => GetMessage("RATING_TYPE_LIKE_TEXT"),
				"like_graphic" => GetMessage("RATING_TYPE_LIKE_GRAPHIC"),
				"standart_text" => GetMessage("RATING_TYPE_STANDART_TEXT"),
				"standart" => GetMessage("RATING_TYPE_STANDART_GRAPHIC"),
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),	
		"SET_TITLE" => array(),
		"CACHE_TIME"  =>  Array("DEFAULT"=>3600),
		"DISPLAY_PANEL" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_PANEL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"),
	),
);
?>
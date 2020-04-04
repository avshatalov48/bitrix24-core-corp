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

$arUGroupsEx = Array();
$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
while($arUGroups = $dbUGroups -> Fetch())
{
	$arUGroupsEx[$arUGroups["ID"]] = $arUGroups["NAME"];
}
$arColumns = array(
	"NAME" => GetMessage("W_TITLE_NAME"),
	"ACTIVE" => GetMessage("W_TITLE_ACTIVE"),
	"SORT" =>  GetMessage("W_TITLE_SORT"),
	"CODE" =>  GetMessage("W_TITLE_CODE"),
	"EXTERNAL_ID" =>  GetMessage("W_TITLE_EXTCODE"),
	"TIMESTAMP_X" =>  GetMessage("W_TITLE_TIMESTAMP"),
//Section specific
	"ELEMENT_CNT" =>  GetMessage("W_TITLE_ELS"),
	"SECTION_CNT" =>  GetMessage("W_TITLE_SECS"),
//Element specific
//	"DATE_ACTIVE_FROM" =>  GetMessage("W_TITLE_ACTFROM"),
//	"DATE_ACTIVE_TO" =>  GetMessage("W_TITLE_ACTTO"), 
	"USER_NAME" =>  GetMessage("W_TITLE_MODIFIED_BY"), 
	"DATE_CREATE" =>  GetMessage("W_TITLE_ADMIN_DCREATE"),
	"CREATED_USER_NAME" =>  GetMessage("W_TITLE_ADMIN_WCREATE2"),
//	"SHOW_COUNTER" =>  GetMessage("W_TITLE_EXTERNAL_SHOWS"),
//	"SHOW_COUNTER_START" =>  GetMessage("W_TITLE_EXTERNAL_SHOW_F"),
//	"PREVIEW_PICTURE" =>  GetMessage("W_TITLE_EXTERNAL_PREV_PIC"),
	"PREVIEW_TEXT" =>  GetMessage("W_TITLE_EXTERNAL_PREV_TEXT"),
//	"DETAIL_PICTURE" =>  GetMessage("W_TITLE_EXTERNAL_DET_PIC"),
	"DETAIL_TEXT" =>  GetMessage("W_TITLE_EXTERNAL_DET_TEXT"),
	"TAGS" =>  GetMessage("W_TITLE_TAGS"),
	"FILE_SIZE" =>  GetMessage("W_TITLE_FILE_SIZE"),

	"ID" => "ID");
if (((CModule::IncludeModule("workflow") && 
			(CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "WORKFLOW") != "N")) ? "Y" : "N"))
{
	$arColumns = array_merge($arColumns, array(
		"WF_STATUS_ID" =>  GetMessage("W_TITLE_STATUS"),
		"WF_NEW" =>  GetMessage("W_TITLE_EXTERNAL_WFNEW"),
		"LOCK_STATUS" =>  GetMessage("W_TITLE_EXTERNAL_LOCK"),
		"LOCKED_USER_NAME" =>  GetMessage("W_TITLE_EXTERNAL_LOCK_BY"),
		"WF_DATE_LOCK" =>  GetMessage("W_TITLE_EXTERNAL_LOCK_WHEN"),
		"WF_COMMENTS" =>  GetMessage("W_TITLE_EXTERNAL_COM")));
}

if (intVal($arCurrentValues["IBLOCK_ID"]) > 0)
{
	$db_res = CIBlockProperty::GetList(
			Array(
				"SORT"=>"ASC",
				"NAME"=>"ASC"
			),
			Array(
				"ACTIVE"=>"Y",
				"IBLOCK_ID"=>$arCurrentValues["IBLOCK_ID"]
			)
		);
	
	while($res = $db_res->GetNext())
		$arColumns["PROPERTY_".$res["ID"]] = $res['NAME'];
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
			"VALUES" => $arIBlock,
			"REFRESH" => (IsModuleInstalled("workflow") ? "Y" : "N")),
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
*/		"NAME_FILE_PROPERTY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_NAME_FILE_PROPERTY"),
			"TYPE" => "STRING",
			"DEFAULT" => 'FILE'), 
		"SORT_BY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_SORT_FIELD"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"ID" => "ID",
				"NAME" => GetMessage("WD_SORT_NAME"),
				"SORT" => GetMessage("WD_SORT_SORT")),
			"DEFAULT" => array("ID")),
		"SORT_ORD" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_SORT_ORDER"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"ASC" => GetMessage("WD_SORT_ASC"),
				"DESC" => GetMessage("WD_SORT_DESC")),
			"DEFAULT" => array("ASC")),
		"BASE_URL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_BASE_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => $GLOBALS["APPLICATION"]->GetCurDir()),

		"SECTIONS" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_LIST_URL"),
			"TYPE" => "STRING",
			"DEFAULT" =>  "PAGE_NAME=sections&PATH=#PATH#"),
		"SECTION_EDIT" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_SECTION_EDIT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#"),
		"ELEMENT" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#"),
		"ELEMENT_EDIT" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_EDIT_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#"),
		"ELEMENT_HISTORY" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_HIST_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#"),
		"ELEMENT_HISTORY_GET" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_ELEMENT_HISTORY_GET_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#"),
		"HELP" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_HELP_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=help"),
		"USER_VIEW" => array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("WD_USER_VIEW_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "PAGE_NAME=user_view&USER_ID=#USER_ID#"),
			
		"COLUMNS" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_IBLOCK_COLUMS"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"MULTIPLE" => "Y",
			"VALUES" => $arColumns,
			"DEFAULT" => array("ID", "NAME", "ACTIVE", "SORT",
		 		"TIMESTAMP_X", "WF_STATUS_ID", "LOCK_STATUS")),
		"PAGE_ELEMENTS" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("PAGE_ELEMENTS"),
			"TYPE" => "STRING",
			"DEFAULT" => 0),
		"PAGE_NAVIGATION_TEMPLATE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("PAGE_NAVIGATION_TEMPLATE"),
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

		"SET_TITLE" => array(),
		"STR_TITLE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_STR_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"CACHE_TIME"  =>  Array("DEFAULT"=>3600),
		"DISPLAY_PANEL" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_NEWS_PANEL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"),
	),
);
?>
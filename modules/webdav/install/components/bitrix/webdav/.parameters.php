<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(!CModule::IncludeModule("iblock"))
	return;

$arIBlockType = array();
$sIBlockType = "";
$arIBlock = array();
$iIblockDefault = 0;
$arUGroupsEx = Array();
$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
$iUploadMaxFilesize = intVal(ini_get('upload_max_filesize'));
$iPostMaxSize = intVal(ini_get('post_max_size'));
$iUploadMaxFilesize = min($iUploadMaxFilesize, $iPostMaxSize);

while($arUGroups = $dbUGroups -> Fetch())
{
	$arUGroupsEx[$arUGroups["ID"]] = $arUGroups["NAME"];
}
$bIBlock = false; 
$arCurrentValues["SEF_MODE"] = "Y"; 

if ($arCurrentValues["RESOURCE_TYPE"] != "FOLDER")
{
	$arCurrentValues["RESOURCE_TYPE"] = "IBLOCK"; 
	$bIBlock = true; 
	$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
	while ($arr=$rsIBlockType->Fetch())
	{
		if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		{
			if ($sIBlockType == "")
				$sIBlockType = $arr["ID"];
			$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
		}
	}
	
	$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
	
	while($arr=$rsIBlock->Fetch())
	{
		if ($iIblockDefault <= 0)
			$iIblockDefault = intVal($arr["ID"]);
		$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
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
//		"DATE_ACTIVE_FROM" =>  GetMessage("W_TITLE_ACTFROM"),
//		"DATE_ACTIVE_TO" =>  GetMessage("W_TITLE_ACTTO"), 
		"USER_NAME" =>	GetMessage("W_TITLE_MODIFIED_BY"), 
		"DATE_CREATE" =>  GetMessage("W_TITLE_ADMIN_DCREATE"),
		"CREATED_USER_NAME" =>	GetMessage("W_TITLE_ADMIN_WCREATE2"),
//		"SHOW_COUNTER" =>  GetMessage("W_TITLE_EXTERNAL_SHOWS"),
//		"SHOW_COUNTER_START" =>  GetMessage("W_TITLE_EXTERNAL_SHOW_F"),
//		"PREVIEW_PICTURE" =>  GetMessage("W_TITLE_EXTERNAL_PREV_PIC"),
		"PREVIEW_TEXT" =>  GetMessage("W_TITLE_EXTERNAL_PREV_TEXT"),
//		"DETAIL_PICTURE" =>  GetMessage("W_TITLE_EXTERNAL_DET_PIC"),
		"DETAIL_TEXT" =>  GetMessage("W_TITLE_EXTERNAL_DET_TEXT"),
		"TAGS" =>  GetMessage("W_TITLE_TAGS"),
		"FILE_SIZE" =>	GetMessage("W_TITLE_FILE_SIZE"),
	
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
			$arColumns["PROPERTY_".$res["CODE"]] = $res["NAME"];
	}
}
else
{
	$arColumns = array(
		"NAME" => GetMessage("W_TITLE_NAME"),
		"FILE_SIZE" =>	GetMessage("W_TITLE_FILE_SIZE"), 
		"TIMESTAMP_X" =>  GetMessage("W_TITLE_TIMESTAMP")); 
}

$arComponentParameters = array(
	"GROUPS" => array(),
	"PARAMETERS" => array(
		"SEF_MODE" => (
			(
				$bIBlock ? 
					array(
						"sections" => array(
							"NAME" => GetMessage("WD_URL_SECTIONS"),
							"DEFAULT" => "#PATH#",
							"VARIABLES" => array("PATH"), 
							"HIDDEN" => "Y"),
						"section_edit" => array(
							"NAME" => GetMessage("WD_URL_SECTION_EDIT"),
							"DEFAULT" => "folder/edit/#SECTION_ID#/#ACTION#/",
							"VARIABLES" => array("SECTION_ID", "ACTION")),
						"element" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT"),
							"DEFAULT" => "element/view/#ELEMENT_ID#/",
							"VARIABLES" => array("ELEMENT_ID")),
						"element_edit" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_EDIT"),
							"DEFAULT" => "element/edit/#ACTION#/#ELEMENT_ID#/",
							"VARIABLES" => array("ELEMENT_ID", "ACTION")),
						"element_history" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_HISTORY"),
							"DEFAULT" => "element/history/#ELEMENT_ID#/",
							"VARIABLES" => array("ELEMENT_ID")),
						"element_history_get" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_HISTORY_GET"),
							"DEFAULT" => "element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#",
							"VARIABLES" => array("ELEMENT_ID", "ELEMENT_NAME")),
						"element_upload" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_UPLOAD"),
							"DEFAULT" => "element/upload/#SECTION_ID#/",
							"VARIABLES" => array("SECTION_ID")),
						"user_view" => array(
							"NAME" => GetMessage("WD_URL_USER_VIEW"),
							"DEFAULT" => "/company/personal/user/#USER_ID#/",
							"VARIABLES" => array("USER_ID")),
						"connector" => array(
							"NAME" => GetMessage("WD_URL_CONNECTOR"),
							"DEFAULT" => "connector/",
							"VARIABLES" => array()),
						"help" => array(
							"NAME" => GetMessage("WD_URL_HELP"),
							"DEFAULT" => "help",
							"VARIABLES" => array()),
					) 
					: 
					array(
						"sections" => array(
							"NAME" => GetMessage("WD_URL_SECTIONS"),
							"DEFAULT" => "#PATH#",
							"VARIABLES" => array("PATH"), 
							"HIDDEN" => "Y"),
						"section_edit" => array(
							"NAME" => GetMessage("WD_URL_SECTION_EDIT"),
							"DEFAULT" => "folder/#ACTION#/edit/#PATH#",
							"VARIABLES" => array("SECTION_ID", "ACTION"), 
							"HIDDEN" => "Y"),
						"element_edit" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_EDIT"),
							"DEFAULT" => "element/#ACTION#/edit/#PATH#",
							"VARIABLES" => array("ELEMENT_ID", "ACTION"), 
							"HIDDEN" => "Y"),
						"element_history_get" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_HISTORY_GET"),
							"DEFAULT" => "element/historyget/#PATH#",
							"VARIABLES" => array("ELEMENT_ID", "ELEMENT_NAME"), 
							"HIDDEN" => "Y"),
						"element_upload" => array(
							"NAME" => GetMessage("WD_URL_ELEMENT_UPLOAD"),
							"DEFAULT" => "element/upload/edit/#PATH#",
							"VARIABLES" => array("SECTION_ID"), 
							"HIDDEN" => "Y"),
						"connector" => array(
							"NAME" => GetMessage("WD_URL_CONNECTOR"),
							"DEFAULT" => "connector/",
							"VARIABLES" => array()),
						"help" => array(
							"NAME" => GetMessage("WD_URL_HELP"),
							"DEFAULT" => "help",
							"VARIABLES" => array()),
					)
				)
			),
		"RESOURCE_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_RESOURCE_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"FOLDER" => GetMessage("WD_RESOURCE_TYPE_FOLDER"), 
				"IBLOCK" => GetMessage("WD_RESOURCE_TYPE_IBLOCK")), 
			"DEFAULT" => "IBLOCK", 
			"REFRESH" => "Y"), 
		"FOLDER" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_PATH_TO_FOLDER"),
			"TYPE" => "STRING",
			"DEFAULT" => "", 
			"HIDDEN" => ($bIBlock ? "Y" : "N")),
		"AUTO_PUBLISH" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_AUTO_PUBLISH"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N", 
			"HIDDEN" => ($bIBlock ? "N" : "Y")),
		"DEFAULT_EDIT" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_DEFAULT_EDIT"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y", 
			"HIDDEN" => "N"),
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
			"DEFAULT" => $sIBlockType, 
			"HIDDEN" => ($bIBlock ? "N" : "Y")),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_IBLOCK_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock, 
			"DEFAULT" => $iIblockDefault, 
			"HIDDEN" => ($bIBlock ? "N" : "Y")),
		"USE_AUTH" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("WD_USE_AUTH"), 
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"),
		"UPLOAD_MAX_FILESIZE" => array(
			"PARENT" => "BASE",
			"NAME" => str_replace("#upload_max_filesize#", $iUploadMaxFilesize, GetMessage("WD_UPLOAD_MAX_FILE_SIZE")),
			"TYPE" => "STRING",
			"DEFAULT" => $iUploadMaxFilesize, 
			"REFRESH" => "Y"),
		"COLUMNS" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_IBLOCK_COLUMS"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => ($bIBlock ? "Y" : "N"),
			"MULTIPLE" => "Y",
			"VALUES" => $arColumns,
			"DEFAULT" => ($bIBlock ? array("NAME", "TIMESTAMP_X", "USER_NAME", "FILE_SIZE") : array("NAME", "TIMESTAMP_X", "FILE_SIZE"))),
		"NAME_TEMPLATE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"TYPE" => "LIST",
			"NAME" => GetMessage("WD_NAME_TEMPLATE"),
			"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "N",
			"DEFAULT" => "",),
//		"PUBLISH_TO_SOCNET" => array(
//			"PARENT" => "ADDITIONAL_SETTINGS",
//			"TYPE" => "CHECKBOX",
//			"NAME" => GetMessage("WD_PUBLISH_TO_SOCNET"),
//			"DEFAULT" => "Y"),
/*		"STR_TITLE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("WD_STR_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),*/
			
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
		/*"AJAX_MODE" => Array()*/
	),
);

if (intval($arCurrentValues["UPLOAD_MAX_FILESIZE"]) > 0):
	$iUploadMaxFilesize = min(intval($arCurrentValues["UPLOAD_MAX_FILESIZE"]), $iUploadMaxFilesize);
endif;

/*
$arComponentParameters["PARAMETERS"]["UPLOAD_MAX_FILE"] = array(
	"PARENT" => "BASE",
	"NAME" => str_replace("#upload_max_file#", ($iPostMaxSize > 0 && $iUploadMaxFilesize > 0 ? 
		round($iPostMaxSize/$iUploadMaxFilesize) : "0. ".GetMessage("WD_UPLOAD_MAX_FILE_ERROR")), GetMessage("WD_UPLOAD_MAX_FILE")),
	"TYPE" => "LIST",
	"VALUES" => array(
		"1" => "1",
		"2" => "2",
		"3" => "3",
		"4" => "4",
		"5" => "5",
		"6" => "6",
		"7" => "7",
		"8" => "8",
		"9" => "9",
		"10" => "10"),
	"DEFAULT" => array("4"),
	"MULTIPLE" => "N");
*/

if (IsModuleInstalled("search") && $bIBlock)
{
	$arComponentParameters["PARAMETERS"]["SEF_MODE"]["search"] = array(
		"NAME" => GetMessage("WD_URL_SEARCH"),
		"DEFAULT" => "search/",
		"VARIABLES" => array());
	$arComponentParameters["GROUPS"]["TAGS_SETTINGS"] = array(
		"NAME" => GetMessage("WD_TAGS_SETTINGS"));
	$arComponentParameters["PARAMETERS"]["SHOW_TAGS"] = array(
			"PARENT" => "TAGS_SETTINGS",
			"NAME" => GetMessage("WD_SHOW_TAGS"), 
			"TYPE" => "CHECKBOX",
			"REFRESH" => (IsModuleInstalled("search") ? "Y" : "N"), 
			"DEFAULT" => "Y");
	if($arCurrentValues["SHOW_TAGS"]=="Y")
	{
		$arComponentParameters["PARAMETERS"]["TAGS_PAGE_ELEMENTS"] = array(
			"PARENT" => "TAGS_SETTINGS",
			"NAME" => GetMessage("SEARCH_PAGE_ELEMENTS"),
			"TYPE" => "STRING",
			"DEFAULT" => "50");
		$arComponentParameters["PARAMETERS"]["TAGS_PERIOD"] = array(
			"PARENT" => "TAGS_SETTINGS",
			"NAME" => GetMessage("SEARCH_PERIOD"),
			"TYPE" => "STRING",
			"DEFAULT" => "");
		$arComponentParameters["PARAMETERS"]["TAGS_INHERIT"] = array(
			"PARENT" => "TAGS_SETTINGS",
			"NAME" => GetMessage("SEARCH_TAGS_INHERIT"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y");
		$arComponentParameters["PARAMETERS"]["TAGS_FONT_MAX"] = array(
			"PARENT" => "TAGS_SETTINGS",
			"NAME" => GetMessage("SEARCH_FONT_MAX"),
			"TYPE" => "STRING",
			"DEFAULT" => "30");
		$arComponentParameters["PARAMETERS"]["TAGS_FONT_MIN"] = array(
			"NAME" => GetMessage("SEARCH_FONT_MIN"),
			"PARENT" => "TAGS_SETTINGS",
			"TYPE" => "STRING",
			"DEFAULT" => "14");
		$arComponentParameters["PARAMETERS"]["TAGS_COLOR_NEW"] = array(
			"NAME" => GetMessage("SEARCH_COLOR_NEW"),
			"PARENT" => "TAGS_SETTINGS",
			"TYPE" => "STRING",
			"DEFAULT" => "486DAA");
		$arComponentParameters["PARAMETERS"]["TAGS_COLOR_OLD"] = array(
			"NAME" => GetMessage("SEARCH_COLOR_OLD"),
			"PARENT" => "TAGS_SETTINGS",
			"TYPE" => "STRING",
			"DEFAULT" => "486DAA");
		$arComponentParameters["PARAMETERS"]["TAGS_SHOW_CHAIN"] = array(
			"NAME" => GetMessage("SEARCH_SHOW_CHAIN"),
			"PARENT" => "TAGS_SETTINGS",
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y");
	}
}

if (IsModuleInstalled("forum") && $bIBlock)
{
	$arComponentParameters["GROUPS"]["REVIEW_SETTINGS"] = array(
		"NAME" => GetMessage("T_IBLOCK_DESC_REVIEW_SETTINGS"));

	$arComponentParameters["PARAMETERS"]["USE_COMMENTS"] = array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_USE_COMMENTS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y");

	if ($arCurrentValues["USE_COMMENTS"]=="Y")
	{
		$arForum = array();
		$fid = 0;
		if (CModule::IncludeModule("forum"))
		{
			$db_res = CForumNew::GetList(array(), array());
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					$arForum[intVal($res["ID"])] = $res["NAME"];
					$fid = intVal($res["ID"]);
				}while ($res = $db_res->Fetch());
			}
		}
		$arComponentParameters["PARAMETERS"]["FORUM_ID"] = Array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("F_FORUM_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arForum,
			"DEFAULT" => $fid);
		$arComponentParameters["PARAMETERS"]["PATH_TO_SMILE"] = Array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("F_PATH_TO_SMILE"),
			"TYPE" => "STRING",
			"DEFAULT" => "/bitrix/images/forum/smile/");
		$arComponentParameters["PARAMETERS"]["USE_CAPTCHA"] = Array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("F_USE_CAPTCHA"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y");
		$arComponentParameters["PARAMETERS"]["PREORDER"] = Array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("F_PREORDER"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y");
	}
}
?>

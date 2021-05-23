<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("iblock"))
	return false;
if(!CModule::IncludeModule("socialnetwork"))
	return false;

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

$arUserGroups = array();
$dbGroups = CGroup::GetList($b = "NAME", $o = "ASC", array("ACTIVE" => "Y"));
while ($arGroup = $dbGroups->GetNext())
	$arUserGroups[$arGroup["ID"]] = "[".$arGroup["ID"]."] ".$arGroup["NAME"];

$arComponentParameters = array(
	"PARAMETERS" => array( 
		"VARIABLE_ALIASES" => Array(
			"meeting_id" => Array(
				"NAME" => GetMessage("SONET_MEETING_VAR"),
				"DEFAULT" => "meeting_id",
			),
			"item_id" => Array(
				"NAME" => GetMessage("SONET_ITEM_VAR"),
				"DEFAULT" => "item_id",
			),
			"page" => Array(
				"NAME" => GetMessage("SONET_PAGE_VAR"),
				"DEFAULT" => "page",
			),
		),
		"SEF_MODE" => Array(
			"index" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_INDEX"),
				"DEFAULT" => "index.php",
				"VARIABLES" => array(),
			),
			"meeting" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_MEETING"),
				"DEFAULT" => "meeting/#meeting_id#/",
				"VARIABLES" => array("meeting_id"),
			),
			"modify_meeting" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_MODIFY_MEETING"),
				"DEFAULT" => "meeting/#meeting_id#/modify/",
				"VARIABLES" => array("meeting_id"),
			),
			"view_item" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_VIEW_ITEM"),
				"DEFAULT" => "meeting/#meeting_id#/view/#item_id#/",
				"VARIABLES" => array("meeting_id", "item_id"),
			),
			"reserve_meeting" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_RESERVE_MEETING"),
				"DEFAULT" => "meeting/#meeting_id#/reserve/#item_id#/",
				"VARIABLES" => array("meeting_id", "item_id"),
			),
			"search" => array(
				"NAME" => GetMessage("SONET_SEF_PATH_SEARCH"),
				"DEFAULT" => "search/",
				"VARIABLES" => array("user_id"),
			),
		),
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
		"SET_NAVCHAIN" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTL_SET_NAVCHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"
		),
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_IRM_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_IRM_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"PATH_TO_USER" => Array(
			"NAME" => GetMessage("INTR_IRM_PARAM_PATH_TO_USER"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/company/personal/user/#USER_ID#/",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_IRM_PARAM_PM_URL'),
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_IRM_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		"SHOW_YEAR" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTR_IRM_PARAM_SHOW_YEAR"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage("INTR_IRM_PARAM_SHOW_YEAR_VALUE_Y"),
				"M" => GetMessage("INTR_IRM_PARAM_SHOW_YEAR_VALUE_M"),
				"N" => GetMessage("INTR_IRM_PARAM_SHOW_YEAR_VALUE_N")
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		),	
		"SET_TITLE" => Array(),
		"USERGROUPS_MODIFY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_USERGROUPS_MODIFY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arUserGroups,
		),
		"USERGROUPS_RESERVE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_USERGROUPS_RESERVE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arUserGroups,
		),
		"USERGROUPS_CLEAR" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_USERGROUPS_CLEAR"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arUserGroups,
		),
		"WEEK_HOLIDAYS" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTL_P_WEEK_HOLIDAYS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => array(GetMessage('INTL_P_MON_F'),GetMessage('INTL_P_TUE_F'),GetMessage('INTL_P_WEN_F'),GetMessage('INTL_P_THU_F'),GetMessage('INTL_P_FRI_F'),GetMessage('INTL_P_SAT_F'),GetMessage('INTL_P_SAN_F')),
			"DEFAULT" => array(5, 6),
		),
	),
);
$arComponentParameters["PARAMETERS"]["DATE_TIME_FORMAT"] = CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_IRM_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS');

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_IRM_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		); 
}

?>
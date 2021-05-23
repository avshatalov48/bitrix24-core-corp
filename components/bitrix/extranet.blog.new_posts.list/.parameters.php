<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("blog"))
	return false;

if(!CModule::IncludeModule("extranet"))
	return false;

$arGroupList = Array();
$dbGroup = CBlogGroup::GetList(Array("SITE_ID" => "ASC", "NAME" => "ASC"));
while($arGroup = $dbGroup->GetNext())
{
	$arGroupList[$arGroup["ID"]] = "(".$arGroup["SITE_ID"].") [".$arGroup["ID"]."] ".$arGroup["NAME"];
}

$arComponentParameters = Array(
	"GROUPS" => array(
		"VARIABLE_ALIASES" => array(
			"NAME" => GetMessage("B_VARIABLE_ALIASES"),
		),
	),
	"PARAMETERS" => Array(
		"MESSAGE_PER_PAGE" => Array(
				"NAME" => GetMessage("EBMNP_MESSAGE_PER_PAGE"),
				"TYPE" => "STRING",
				"DEFAULT" => 6,
				"PARENT" => "VISUAL",
			),
		"DATE_TIME_FORMAT" => Array(
				"PARENT" => "VISUAL",
				"NAME" => GetMessage("BC_DATE_TIME_FORMAT"),
				"TYPE" => "LIST",
				"VALUES" => CBlogTools::GetDateTimeFormat(),
				"MULTIPLE" => "N",
				"DEFAULT" => $GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL")),	
				"ADDITIONAL_VALUES" => "Y",
			),		
		"PATH_TO_BLOG" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_BLOG"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_POST" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_POST"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_USER" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_USER"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_BLOG_POST" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_GROUP_BLOG_POST"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_BLOG_CATEGORY" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_BLOG_CATEGORY"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "?category_name=#category_name#",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),		
		"PATH_TO_SMILE" => Array(
			"NAME" => GetMessage("EBMNP_PATH_TO_SMILE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"BLOG_VAR" => Array(
			"NAME" => GetMessage("EBMNP_BLOG_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "blog",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"POST_VAR" => Array(
			"NAME" => GetMessage("EBMNP_POST_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "id",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"USER_VAR" => Array(
			"NAME" => GetMessage("EBMNP_USER_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "id",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PAGE_VAR" => Array(
			"NAME" => GetMessage("EBMNP_PAGE_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "page",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"CATEGORY_NAME_VAR" => Array(
			"NAME" => GetMessage("EBMNP_CATEGORY_NAME_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "category_name",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),		
		"CACHE_TIME"	=>	array("DEFAULT"=>"86400"),
		"GROUP_ID"=>array(
			"NAME" => GetMessage("BLG_GROUP_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arGroupList,
			"MULTIPLE" => "N",
			"DEFAULT" => "",	
			"ADDITIONAL_VALUES" => "Y",
			"PARENT" => "DATA_SOURCE",
		),
		"NAV_TEMPLATE" => array(
			"PARENT" => "VISUAL",
			"NAME" => GetMessage("BB_NAV_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"SET_TITLE"		=>	Array(),
	)
);?>
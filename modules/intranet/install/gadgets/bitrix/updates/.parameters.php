<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:socialnetwork.log.ex", $arCurrentValues);

$arEntityType = Array(
	"" 					=> GetMessage("GD_LOG_P_ENTITY_TYPE_VALUE_ALL"), 
	SONET_ENTITY_USER 	=> GetMessage("GD_LOG_P_ENTITY_TYPE_VALUE_USER"), 
	SONET_ENTITY_GROUP 	=> GetMessage("GD_LOG_P_ENTITY_TYPE_VALUE_GROUP"),
);

$arEventID = Array(
	"" 					=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_ALL"), 
	"system" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_SYSTEM"), 
	"system_groups" 	=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_SYSTEM_GROUPS"), 
	"forum" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_FORUM"), 
	"blog" 				=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_BLOG"), 
	"photo" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_PHOTO"), 
	"calendar" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_CALENDAR"), 
	"files" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_FILES"), 
	"tasks" 			=> GetMessage("GD_LOG_P_EVENT_ID_VALUE_TASKS"), 
);

if (CModule::IncludeModule("socialnetwork") && CSocNetUser::IsFriendsAllowed())
	$arEventID["system_friends"] = GetMessage("GD_LOG_P_EVENT_ID_VALUE_SYSTEM_FRIENDS");

$arParameters = Array(
		"PARAMETERS"=> Array(
			"USER_VAR"		=> $arComponentProps["PARAMETERS"]["USER_VAR"],
			"GROUP_VAR"		=> $arComponentProps["PARAMETERS"]["GROUP_VAR"],
			"PAGE_VAR"		=> $arComponentProps["PARAMETERS"]["PAGE_VAR"],
			"PATH_TO_USER"	=> $arComponentProps["PARAMETERS"]["PATH_TO_USER"],
			"PATH_TO_GROUP"	=> $arComponentProps["PARAMETERS"]["PATH_TO_GROUP"],
			"LIST_URL"		=> Array(
				"NAME" 			=> GetMessage("GD_LOG_P_URL"),
				"TYPE" 			=> "STRING",
				"MULTIPLE" 		=> "N",
				"DEFAULT" 		=> "/company/personal/log/",
			),

		),

		"USER_PARAMETERS"=> Array(
			"LOG_CNT" => Array(
				"NAME" 		=> GetMessage("GD_LOG_P_LOG_CNT"),
				"TYPE" 		=> "STRING",
				"DEFAULT" 	=> "7"
			),
			"ENTITY_TYPE" => Array(
				"NAME" 		=> GetMessage("GD_LOG_P_ENTITY_TYPE"),
				"TYPE" 		=> "LIST",
				"VALUES" 	=> $arEntityType,
				"MULTIPLE" 	=> "N",
				"DEFAULT" 	=> ""
			),
			"EVENT_ID" => Array(
				"NAME" 		=> GetMessage("GD_LOG_P_EVENT_ID"),
				"TYPE" 		=> "LIST",
				"VALUES" 	=> $arEventID,
				"MULTIPLE" 	=> "Y",
				"DEFAULT" 	=> ""
			),
			"AVATAR_SIZE" => Array(
				"NAME" 		=> GetMessage("GD_LOG_AVATAR_SIZE"),
				"TYPE" 		=> "STRING",
				"MULTIPLE" 	=> "N",
				"DEFAULT" 	=> "",
				"COLS" 		=> 3,
			),
			"AVATAR_SIZE_COMMENT" => Array(
				"NAME" 		=> GetMessage("GD_LOG_AVATAR_SIZE_COMMENT"),
				"TYPE" 		=> "STRING",
				"MULTIPLE" 	=> "N",
				"DEFAULT" 	=> "",
				"COLS" 		=> 3,
			)
		),

	);


$arParameters["PARAMETERS"]["USER_VAR"]["DEFAULT"] = "user_id";
$arParameters["PARAMETERS"]["GROUP_VAR"]["DEFAULT"] = "group_id";
$arParameters["PARAMETERS"]["PAGE_VAR"]["DEFAULT"] = "page";
$arParameters["PARAMETERS"]["PATH_TO_USER"]["DEFAULT"] = "/company/personal/user/#user_id#/";
$arParameters["PARAMETERS"]["PATH_TO_GROUP"]["DEFAULT"] = "/workgroups/group/#group_id#/";
?>
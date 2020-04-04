<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:tasks.list", $arCurrentValues);

$arParameters = Array(
	"PARAMETERS" => Array(
		"PAGE_VAR" => $arComponentProps["PARAMETERS"]["PAGE_VAR"],
		"GROUP_VAR" => $arComponentProps["PARAMETERS"]["GROUP_VAR"],
		"TASK_VAR" => $arComponentProps["PARAMETERS"]["TASK_VAR"],
		"ACTION_VAR" =>$arComponentProps["PARAMETERS"]["ACTION_VAR"],
		"PATH_TO_GROUP_TASKS" => $arComponentProps["PARAMETERS"]["PATH_TO_GROUP_TASKS"],
		"PATH_TO_GROUP_TASKS_TASK" => $arComponentProps["PARAMETERS"]["PATH_TO_GROUP_TASKS_TASK"],
		"PATH_TO_USER_TASKS" => $arComponentProps["PARAMETERS"]["PATH_TO_USER_TASKS"],
		"PATH_TO_USER_TASKS_TASK" => $arComponentProps["PARAMETERS"]["PATH_TO_USER_TASKS_TASK"],
		"PATH_TO_TASK"=> Array(
			"NAME" => GetMessage("GD_TASKS_P_PATH_TO_TASK"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/user/#user_id#/tasks/",
		),
		"PATH_TO_TASK_NEW"=> Array(
			"NAME" => GetMessage("GD_TASKS_P_PATH_TO_TASK_NEW"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/user/#user_id#/tasks/task/create/0/",
		),
	),
	"USER_PARAMETERS" => Array(
		"ITEMS_COUNT" => $arComponentProps["PARAMETERS"]["ITEMS_COUNT"],
		"ORDER_BY" => array(
			"NAME" => GetMessage("GD_TASKS_P_ORDER_BY"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"E" => GetMessage("GD_TASKS_P_ORDER_BY_D1"),
				"C" => GetMessage("GD_TASKS_P_ORDER_BY_D2"),
				"P" => GetMessage("GD_TASKS_P_ORDER_BY_D3"),
			),
		),
		"TYPE" => array(
			"NAME" => GetMessage("GD_TASKS_P_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Z" => GetMessage("GD_TASKS_P_TYPE_Z"),
				"U" => GetMessage("GD_TASKS_P_TYPE_U"),
			),
		),
	),
);

$arParameters["PARAMETERS"]["PATH_TO_GROUP_TASKS"]["DEFAULT"] = "/workgroups/group/#group_id#/tasks/";
$arParameters["PARAMETERS"]["PATH_TO_GROUP_TASKS_TASK"]["DEFAULT"] = "/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/";
$arParameters["PARAMETERS"]["PATH_TO_USER_TASKS"]["DEFAULT"] = "/company/personal/user/#user_id#/tasks/";
$arParameters["PARAMETERS"]["PATH_TO_USER_TASKS_TASK"]["DEFAULT"] = "/company/personal/user/#user_id#/tasks/task/#action#/#task_id#/";
$arParameters["PARAMETERS"]["PAGE_VAR"]["DEFAULT"] = "page";
$arParameters["PARAMETERS"]["GROUP_VAR"]["DEFAULT"] = "group_id";
$arParameters["PARAMETERS"]["TASK_VAR"]["DEFAULT"] = "task_id";
$arParameters["PARAMETERS"]["ACTION_VAR"]["DEFAULT"] = "action";

?>
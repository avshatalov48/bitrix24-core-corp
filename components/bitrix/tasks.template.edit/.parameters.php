<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("tasks"))
	return;


$arComponentParameters = Array(
	"GROUPS" => array(
		"VARIABLE_ALIASES" => array(
			"NAME" => GetMessage("TASKS_PARAM_VARIABLE_ALIASES"),
		),
	),
	"PARAMETERS" => Array(
		"TASK_ID" => array(
			"NAME" => GetMessage("TASKS_PARAM_TASK_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"USER_ID" => array(
			"NAME" => GetMessage("TASKS_PARAM_USER_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"GROUP_ID" => Array(
			"NAME" => GetMessage("TASKS_PARAM_GROUP_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		),
		"TASK_VAR" => Array(
			"NAME" => GetMessage("TASKS_PARAM_TASK_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"USER_VAR" => Array(
			"NAME" => GetMessage("TASKS_PARAM_USER_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"GROUP_VAR" => Array(
			"NAME" => GetMessage("TASKS_PARAM_GROUP_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"ACTION_VAR" => Array(
			"NAME" => GetMessage("TASKS_PARAM_ACTION_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PAGE_VAR" => Array(
			"NAME" => GetMessage("TASKS_PARAM_PAGE_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PATH_TO_GROUP_TASKS" => Array(
			"NAME" => GetMessage("TASKS_PARAM_PATH_TO_GROUP_TASKS"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_TASKS_TASK" => Array(
			"NAME" => GetMessage("TASKS_PARAM_PATH_TO_GROUP_TASKS_TASK"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_USER_TASKS" => Array(
			"NAME" => GetMessage("TASKS_PARAM_PATH_TO_USER_TASKS"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_USER_TASKS_TASK" => Array(
			"NAME" => GetMessage("TASKS_PARAM_PATH_TO_USER_TASKS_TASK"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"SET_TITLE" => Array(),
		"CACHE_TIME" => Array("DEFAULT"=>"3600"),
	)
);
?>
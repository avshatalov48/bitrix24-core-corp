<?php
/** @global CUser $USER */

if (!CModule::IncludeModule("controller"))
	return false;

IncludeModuleLangFile(__FILE__);

$aMenu = array(
	"parent_menu" => "global_menu_services",
	"section" => "controller",
	"sort" => 100,
	"text" => GetMessage("CTRLR_MENU_NAME"),
	"title" => GetMessage("CTRLR_MENU_TITLE"),
	"icon" => "controller_menu_icon",
	"page_icon" => "controller_page_icon",
	"items_id" => "menu_controller",
	"more_url" => array(),
	"items" => array()
);

if ($USER->CanDoOperation("controller_member_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_SITE_NAME"),
		"url" => "controller_member_admin.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(
			"controller_member_edit.php?lang=".LANG,
			"controller_member_history.php?lang=".LANG,
		),
		"items_id" => "menu_controller_member_",
		"title" => GetMessage("CTRLR_MENU_SITE_TITLE"),
		"items" => Array(),
	);
}

if ($USER->CanDoOperation("controller_group_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_GROUP_NAME"),
		"url" => "controller_group_admin.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(
			"controller_group_edit.php?lang=".LANG,
		),
		"items_id" => "menu_controller_group",
		"title" => GetMessage("CTRLR_MENU_GROUP_TYPE"),
	);
}

if ($USER->CanDoOperation("controller_task_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_TASK_NAME"),
		"url" => "controller_task.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(
			"controller_task.php?lang=".LANG,
		),
		"items_id" => "menu_controller_task",
		"title" => GetMessage("CTRLR_MENU_TASK_TITLE"),
	);
}

if ($USER->CanDoOperation("controller_log_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_LOG_NAME"),
		"url" => "controller_log_admin.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(),
		"items_id" => "menu_controller_log",
		"title" => GetMessage("CTRLR_MENU_LOG_TITLE"),
	);
}

if ($USER->CanDoOperation("controller_member_updates_run") && ControllerIsSharedMode())
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_UPD_NAME"),
		"url" => "controller_update.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(),
		"title" => GetMessage("CTRLR_MENU_UPD_TYPE"),
	);
}

if ($USER->CanDoOperation("controller_run_command"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_RUN_NAME"),
		"url" => "controller_run_command.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(),
		"title" => GetMessage("CTRLR_MENU_RUN_TITLE"),
	);
}

if ($USER->CanDoOperation("controller_upload_file"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_UPLOAD_NAME"),
		"url" => "controller_upload_file.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(),
		"title" => GetMessage("CTRLR_MENU_UPLOAD_TITLE"),
	);
}

if ($USER->CanDoOperation("controller_counters_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_COUNTERS"),
		"url" => "controller_counter_admin.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(
			"controller_counter_edit.php?lang=".LANG,
		),
		"items_id" => "menu_controller_counter",
		"title" => GetMessage("CTRLR_MENU_COUNTERS_TITLE"),
	);
}

if ($USER->CanDoOperation("controller_auth_view"))
{
	$aMenu["items"][] = array(
		"text" => GetMessage("CTRLR_MENU_AUTH"),
		"url" => "controller_auth.php?lang=".LANG,
		"module_id" => "controller",
		"more_url" => array(
			"controller_group_map.php",
			"controller_auth_log.php",
		),
		"items_id" => "menu_controller_auth",
		"title" => "",
	);
}

if ($aMenu["items"])
	return $aMenu;
else
	return false;

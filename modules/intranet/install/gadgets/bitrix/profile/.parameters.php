<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParameters = Array(
		"PARAMETERS"=> Array(
			"PATH_TO_GENERAL"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_GENERAL"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/",
			),
			"PATH_TO_PROFILE_EDIT"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_PROFILE_EDIT"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/edit/",
			),
			"PATH_TO_LOG"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_LOG"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/log/",
			),
			"PATH_TO_SUBSCR"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_SUBSCR"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/subscribe/",
			),
			"PATH_TO_MSG"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_MSG"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/messages/",
			),
			"PATH_TO_GROUPS"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_GROUPS"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/groups/",
			),
			"PATH_TO_GROUP_NEW"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_GROUP_NEW"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/groups/create/",
			),
			"PATH_TO_PHOTO"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_PHOTO"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/photo/",
			),
			"PATH_TO_PHOTO_NEW"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_PHOTO_NEW"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/photo/photo/0/action/upload/",
			),
			"PATH_TO_FORUM"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_FORUM"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/forum/",
			),
			"PATH_TO_BLOG"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_BLOG"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/blog/",
			),
			"PATH_TO_BLOG_NEW"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_BLOG_NEW"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/blog/edit/new/",
			),
			"PATH_TO_CAL"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_CAL"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/calendar/",
			),
			"PATH_TO_TASK"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_TASK"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/tasks/",
			),
			"PATH_TO_TASK_NEW"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_TASK_NEW"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/tasks/task/create/0/",
			),
			"PATH_TO_LIB"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_PATH_TO_LIB"),
				"TYPE" => "STRING",
				"DEFAULT" => "/company/personal/user/#user_id#/files/lib/",
			),
		),
		"USER_PARAMETERS"=> Array(
			"SHOW_GENERAL"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_GENERAL"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_GROUPS"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_GROUPS"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_PHOTO"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_PHOTO"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_FORUM"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_FORUM"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_CAL"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_CAL"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_BLOG"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_BLOG"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_TASK"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_TASK"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
			"SHOW_LIB"=> Array(
				"NAME" => GetMessage("GD_PROFILE_P_SHOW_LIB"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
		),
	);
?>

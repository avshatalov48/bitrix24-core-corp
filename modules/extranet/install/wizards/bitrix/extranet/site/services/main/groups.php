<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (
	defined("WIZARD_IS_RERUN")
	&& WIZARD_IS_RERUN === true
	&& !file_exists(WIZARD_SITE_PATH.".superleft.menu.php")
)
{
	return;
}

$arGroups = Array(
	Array(
		"ACTIVE" => "Y",
		"C_SORT" => 103,
		"NAME" => GetMessage("EXTRANET_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_GROUP_DESC"),
		"STRING_ID" => "EXTRANET",
		"TASKS_MODULE" => Array("main_change_profile"),
		"TASKS_FILE" => Array(
			Array("fm_folder_access_read", "/bitrix/components/bitrix/"),
			Array("fm_folder_access_read", "/bitrix/gadgets/bitrix/"),
			Array("fm_folder_access_read", "/bitrix/tools/"),
			Array("fm_folder_access_read", "/bitrix/services/mobile/"),
			Array("fm_folder_access_read", "/bitrix/services/mobileapp/"),
			Array("fm_folder_access_read", "/upload/"),
			Array("fm_folder_access_read", WIZARD_SITE_DIR),
		),
	),

	Array(
		"ACTIVE" => "Y",
		"C_SORT" => 104,
		"NAME" => GetMessage("EXTRANET_CREATE_WG_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_CREATE_WG_GROUP_DESC"),
		"STRING_ID" => "EXTRANET_CREATE_WG",
		"TASKS_MODULE" => Array("main_change_profile"),
		"TASKS_FILE" => Array(
			Array("fm_folder_access_read", WIZARD_SITE_DIR),
		),
	),

	Array(
		"ACTIVE" => "Y",
		"C_SORT" => 105,
		"NAME" => GetMessage("EXTRANET_ADMIN_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_ADMIN_GROUP_DESC"),
		"STRING_ID" => "EXTRANET_ADMIN",
		"TASKS_MODULE" => Array("main_edit_subordinate_users"),
		"TASKS_FILE" => Array(
			Array("fm_folder_access_read", "/bitrix/admin/"),
			Array("fm_folder_access_read", "/bitrix/components/bitrix/"),
			Array("fm_folder_access_read", "/bitrix/gadgets/bitrix/"),
			Array("fm_folder_access_read", "/bitrix/tools/"),
			Array("fm_folder_access_read", "/bitrix/services/mobile/"),
			Array("fm_folder_access_read", "/bitrix/services/mobileapp/"),
			Array("fm_folder_access_full", WIZARD_SITE_DIR),
		),
	)
);

$GLOBALS["APPLICATION"]->SetFileAccessPermission(WIZARD_SITE_DIR, array("*" => "D"));

$group = new CGroup;
foreach ($arGroups as $arGroup)
{
	//Add Group
	$groupID = 0;

	$dbResult = CGroup::GetList('', '', Array("STRING_ID" => $arGroup["STRING_ID"], "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
	{
		$groupID = $arExistsGroup["ID"];
	}
	else
	{
		$groupID = $group->Add($arGroup);
	}

	if ($groupID <= 0)
		continue;

	if ($arGroup["STRING_ID"] == "EXTRANET_ADMIN")
		$ExtranetAdminGroupID = $groupID;

	if ($arGroup["STRING_ID"] == "EXTRANET")
	{
		COption::SetOptionString("extranet", "extranet_group", $groupID);
		if (!defined('WIZARD_EXTRANET_GROUP'))
		{
			define('WIZARD_EXTRANET_GROUP', $groupID);
		}
	}

	if ($arGroup['STRING_ID'] == 'EXTRANET_ADMIN')
	{
		if (!defined('WIZARD_EXTRANET_ADMIN_GROUP'))
		{
			define('WIZARD_EXTRANET_ADMIN_GROUP', $groupID);
		}
	}

	if ($arGroup['STRING_ID'] == 'EXTRANET_CREATE_WG')
	{
		if (!defined('WIZARD_EXTRANET_CREATE_WG_GROUP'))
		{
			define('WIZARD_EXTRANET_CREATE_WG_GROUP', $groupID);
		}
	}

	if (!file_exists(WIZARD_SITE_PATH.".superleft.menu.php")) // don't use in cloud->box master
	{
		//Set tasks binding to module
		$arTasksID = Array();
		foreach ($arGroup["TASKS_MODULE"] as $taskName)
		{
			$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
			if ($arTask = $dbResult->Fetch())
				$arTasksID[] = $arTask["ID"];
		}

		if (!empty($arTasksID))
			CGroup::SetTasks($groupID, $arTasksID);
	}

	//Set tasks binding to file
	foreach ($arGroup["TASKS_FILE"] as $arFile)
	{
		$taskName = $arFile[0];
		$filePath = $arFile[1];

		$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
		if ($arTask = $dbResult->Fetch())
		{
			CExtranetWizardServices::SetFilePermission(Array(WIZARD_SITE_ID, $filePath), Array($groupID => "T_".$arTask["ID"]));
		}
	}
}

if (defined('WIZARD_EXTRANET_ADMIN_GROUP') && defined('WIZARD_EXTRANET_GROUP'))
{
	CGroup::SetSubordinateGroups(WIZARD_EXTRANET_ADMIN_GROUP, Array(WIZARD_EXTRANET_GROUP));
}

// set view perms for employee groups
$rsGroupEmployees = CGroup::GetList("c_sort", "asc", Array("STRING_ID" => "EMPLOYEES%"));
while ($arGroupEmployees = $rsGroupEmployees->Fetch())
{
	$dbResult = CTask::GetList(array(), Array("NAME" => "fm_folder_access_read"));
	if ($arTask = $dbResult->Fetch())
	{
		CExtranetWizardServices::SetFilePermission(array(WIZARD_SITE_ID, WIZARD_SITE_DIR), array($arGroupEmployees["ID"] => "T_".$arTask["ID"]));
	}
}

if (!file_exists(WIZARD_SITE_PATH.".superleft.menu.php")) // don't use in cloud->box master
{
	$APPLICATION->SetGroupRight("fileman", WIZARD_EXTRANET_ADMIN_GROUP, "F");
	$task_id = CTask::GetIdByLetter("F", "fileman");
	if (intval($task_id) > 0)
		CGroup::SetTasksForModule("fileman", array(WIZARD_EXTRANET_ADMIN_GROUP => array("ID" => $task_id)));


	CWizardUtil::ReplaceMacros(
		WIZARD_SITE_PATH."/.top.menu.php",
		Array(
			"EXTRANET_ADMIN_GROUP_ID" => $ExtranetAdminGroupID,
		)
	);

	$rsUser = CUser::GetList("ID", "desc", array("GROUPS_ID"=>array(1)));
	while($arAdminUser = $rsUser->Fetch())
	{
		$arUserGroups = CUser::GetUserGroup($arAdminUser["ID"]);
		if (is_array($arUserGroups) && !in_array(WIZARD_EXTRANET_GROUP, $arUserGroups))
		{
			$arUserGroups[] = WIZARD_EXTRANET_GROUP;
			CUser::SetUserGroup($arAdminUser["ID"], $arUserGroups);
		}
	}
}

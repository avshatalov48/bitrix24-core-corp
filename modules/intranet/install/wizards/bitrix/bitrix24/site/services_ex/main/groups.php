<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arGroups = Array(
	Array(
		"~ID" => "13",
		"ACTIVE" => "Y",
		"C_SORT" => 103,
		"NAME" => GetMessage("EXTRANET_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_GROUP_DESC"),
		"STRING_ID" => "EXTRANET",
		"TASKS_MODULE" => Array("main_change_profile"),
		"TASKS_FILE" => Array(
			Array("fm_folder_access_read", "/bitrix/components/bitrix/"),
			Array("fm_folder_access_read", "/bitrix/rk.php"),
			Array("fm_folder_access_read", "/bitrix/tools/"),
			Array("fm_folder_access_read", "/bitrix/services/mobile/"),
			Array("fm_folder_access_read", "/bitrix/services/mobileapp/"),
			Array("fm_folder_access_read", WIZARD_SITE_DIR),
		),
	)
);
if (!WIZARD_IS_INSTALLED)
	$GLOBALS["APPLICATION"]->SetFileAccessPermission(WIZARD_SITE_DIR, array("*" => "D"));

$group = new CGroup;
foreach ($arGroups as $arGroup)
{
	//Add Group
	$dbResult = CGroup::GetList('', '', Array("STRING_ID" => $arGroup["STRING_ID"], "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
		$groupID = $arExistsGroup["ID"];
	else
		$groupID = $group->Add($arGroup);

	if ($groupID <= 0)
		continue;

	if ($arGroup["STRING_ID"] == "EXTRANET")
	{
		COption::SetOptionString("extranet", "extranet_group", $groupID);
		define("WIZARD_EXTRANET_GROUP", $groupID);
	}

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

	if(!WIZARD_IS_INSTALLED)
	{
		/*if ($arGroup["STRING_ID"] == "EXTRANET")
			WizardServices::SetFilePermission(Array($_SERVER['DOCUMENT_ROOT'], WIZARD_SITE_DIR), Array($groupID => "R"));*/
			
		//Set tasks binding to file
		foreach ($arGroup["TASKS_FILE"] as $arFile)
		{
			$taskName = $arFile[0];
			$filePath = $arFile[1];

			$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
			if ($arTask = $dbResult->Fetch())
				WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, $filePath), Array($groupID => "T_".$arTask["ID"]));
		}
	}
}

$rsUser = CUser::GetList("ID", "desc", array("GROUPS_ID"=>array(1)));
while($arAdminUser = $rsUser->Fetch())
{
	CUser::AppendUserGroup($arAdminUser["ID"], WIZARD_EXTRANET_GROUP);
}
?>
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
	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => $arGroup["STRING_ID"], "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
		$groupID = $arExistsGroup["ID"];
	else
		$groupID = $group->Add($arGroup);

	if ($arGroup["STRING_ID"] == "EXTRANET_ADMIN")
		$ExtranetAdminGroupID = $groupID;
		
	if ($groupID <= 0)
		continue;

	if ($arGroup["STRING_ID"] == "EXTRANET")
	{
		COption::SetOptionString("extranet", "extranet_group", $groupID);
		define("WIZARD_EXTRANET_GROUP", $groupID);
	}
			
	if ($arGroup["STRING_ID"] == "EXTRANET_ADMIN")
		define("WIZARD_EXTRANET_ADMIN_GROUP", $groupID);

	if ($arGroup["STRING_ID"] == "EXTRANET_SUPPORT")
		define("WIZARD_EXTRANET_SUPPORT_GROUP", $groupID);

	if ($arGroup["STRING_ID"] == "EXTRANET_CREATE_WG")
		define("WIZARD_EXTRANET_CREATE_WG_GROUP", $groupID);
			
	//Set tasks binding to module
	$arTasksID = Array();
	foreach ($arGroup["TASKS_MODULE"] as $taskName)
	{
		$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
		if ($arTask = $dbResult->Fetch())
			$arTasksID[] = $arTask["ID"];
	}

	if (!empty($arTasksID))
		CGroup::SetTasks($groupID, $arTasksID, true);

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

$APPLICATION->SetGroupRight("fileman", WIZARD_EXTRANET_ADMIN_GROUP, "F");
$task_id = CTask::GetIdByLetter("F", "fileman");
if (intval($task_id) > 0)
	CGroup::SetTasksForModule("fileman", array(WIZARD_EXTRANET_ADMIN_GROUP => array("ID" => $task_id)));


/*if(CModule::IncludeModule('fileman'))
{

	$menuItem = array(
					GetMessage("EXTRANET_MENUITEM_NAME"),
					WIZARD_SITE_DIR,
					array(),
					array(),
					"CSite::InGroup(array(1,".WIZARD_EXTRANET_ADMIN_GROUP.",".WIZARD_EXTRANET_GROUP."))",
				);


	$arSiteIntranet = false;
	$arSiteTemplateIntranet = false;
	
	$rsSites = CSite::GetList($by="sort", $order="desc", array());
	while ($arSite = $rsSites->Fetch())
	{
		if ($arSite["ID"] != WIZARD_SITE_ID)
		{
			$arSiteIntranet = $arSite;
			break;
		}
	}

	if ($arSiteIntranet)
	{
		$rsSiteTemplates = CSite::GetTemplateList($arSiteIntranet["ID"]);
		while ($arSiteTemplate = $rsSiteTemplates->Fetch())
		{
			if (strlen($arSiteTemplate["CONDITION"]) <= 0)
			{
				$arSiteTemplateIntranet = $arSiteTemplate;
				break;
			}
		}
	}

	if ($arSiteTemplateIntranet && $arSiteTemplateIntranet["TEMPLATE"] == "light")
		$menuType = "top_links";
	else
		$menuType = "top";

	$arResult = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"]."/.".$menuType.".menu.php");
	$arMenuItems = $arResult["aMenuLinks"];
	$menuTemplate = $arResult["sMenuTemplate"];

	$bFound = false;
	foreach($arMenuItems as $item)
		if($item[1] == $menuItem[1])
			$bFound = true;

	if(!$bFound)
	{
		$arMenuItems[] = $menuItem;

		$rsSites = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
		while ($arSite = $rsSites->Fetch())
		{
			if ($arSite["ID"] != WIZARD_SITE_ID)
			{
				$intranetSiteID = $arSite["ID"];
				CFileMan::SaveMenu(Array($intranetSiteID, "/.".$menuType.".menu.php"), $arMenuItems, $menuTemplate);
				break;
			}
		}
	
	}
}    */

$rsUser = CUser::GetList(($by="ID"), ($order="desc"), array("GROUPS_ID"=>array(1)));
while($arAdminUser = $rsUser->Fetch())
{
	CUser::AppendUserGroup($arAdminUser["ID"], WIZARD_EXTRANET_GROUP);
}
?>
<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
/*
$arTask = Array(
	"NAME" => "editors_task",
	"MODULE_ID" => "main",
	"BINDING" => "module",
	"SYS" => "N",
	"LETTER" => "P",
);

$taskID = CTask::Add($arTask);
if (intval($taskID) > 0)
{
	CTask::SetOperations(
		$taskID,
		Array("edit_own_profile", "view_own_profile", "cache_control"),
		$bOpNames = true
	);
}
*/


$arGroups = Array();

$arGroups[] = Array(
	"~ID" => "11",
	"ACTIVE" => "Y",
	"C_SORT" => 3,
	"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("EMPLOYEES_GROUP_NAME"),
	"DESCRIPTION" => GetMessage("EMPLOYEES_GROUP_DESC"),
	"STRING_ID" => "EMPLOYEES_".WIZARD_SITE_ID,
	"TASKS_MODULE" => Array("main_change_profile"),
	"TASKS_FILE" => Array(
	),
);
$arGroups[] = Array(
	"~ID" => "12",
	"ACTIVE" => "Y",
	"C_SORT" => 6,
	"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("PORTAL_ADMINISTRATION_GROUP_NAME"),
	"DESCRIPTION" => GetMessage("PORTAL_ADMINISTRATION_GROUP_DESC"),
	"STRING_ID" => "PORTAL_ADMINISTRATION_".WIZARD_SITE_ID,
	"TASKS_MODULE" => Array("main_edit_subordinate_users"),
	"TASKS_FILE" => Array(
		Array("fm_folder_access_full", WIZARD_SITE_DIR),
		Array("fm_folder_access_read", "/bitrix/admin/"),
	),
);
$arGroups[] = Array(
	"~ID" => "6",
	"ACTIVE" => "Y",
	"C_SORT" => 6,
	"NAME" => GetMessage("INTEGRATOR_GROUP_NAME"),
	"DESCRIPTION" => GetMessage("INTEGRATOR_GROUP_NAME"),
	"STRING_ID" => "INTEGRATOR",
	"TASKS_MODULE" => Array(),
	"TASKS_FILE" => Array(),
);

$SiteGroup = array();
$SiteGroups = array();
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

	$SiteGroup["STRING_ID"] = $arGroup["STRING_ID"];
	$SiteGroups[$arGroup["STRING_ID"]] = $groupID;

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
		//Set tasks binding to file
		foreach ($arGroup["TASKS_FILE"] as $arFile)
		{
			$taskName = $arFile[0];
			$filePath = $arFile[1];

			$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
			if ($arTask = $dbResult->Fetch())
				WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, $filePath), Array($groupID => "T_".$arTask["ID"]));
		}
		if ($arGroup["STRING_ID"] == "EMPLOYEES_".WIZARD_SITE_ID)
		{
			WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array("*" => 'D'));
			WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array($groupID => 'R'));
		}
	}
}

$dbGroupUsers = CGroup::GetList("id", "asc", Array("ACTIVE" => "Y"));
$arGroupsId = Array("ADMIN_SECTION", "SUPPORT", "CREATE_GROUPS", "PERSONNEL_DEPARTMENT", "DIRECTION", "MARKETING_AND_SALES");
while($arGroupUser = $dbGroupUsers->Fetch())
{
	if(in_array($arGroupUser["STRING_ID"], $arGroupsId))
	{
		define("WIZARD_".$arGroupUser["STRING_ID"]."_GROUP", $arGroupUser["ID"]);
	}
	else
	{
		if(mb_substr($arGroupUser["STRING_ID"], -2) == WIZARD_SITE_ID)
			define("WIZARD_".mb_substr($arGroupUser["STRING_ID"], 0, -3)."_GROUP", $arGroupUser["ID"]);
	}
}

//admin security policy
$z = CGroup::GetByID(1);
if($res = $z->Fetch())
{
	if($res["SECURITY_POLICY"] == "")
	{
		$group = new CGroup;
		$arGroupPolicy = array(
			"SESSION_TIMEOUT" => 15, //minutes
			"SESSION_IP_MASK" => "255.255.255.255",
			"MAX_STORE_NUM" => 1,
			"STORE_IP_MASK" => "255.255.255.255",
			"STORE_TIMEOUT" => 60*24*3, //minutes
			"CHECKWORD_TIMEOUT" => 60,  //minutes
			"PASSWORD_LENGTH" => 6,
			"PASSWORD_UPPERCASE" => "N",
			"PASSWORD_LOWERCASE" => "N",
			"PASSWORD_DIGITS" => "Y",
			"PASSWORD_PUNCTUATION" => "N",
			"LOGIN_ATTEMPTS" => 3,
		);
		$arFields = array(
			"SECURITY_POLICY" => serialize($arGroupPolicy)
		);
//		$group->Update(1, $arFields);
	}

	$groupAdmin = 1;
	$dbTasks = CTask::GetList(Array(), Array("NAME" => 'webdav_full_access'));
	if ($arTask = $dbTasks->Fetch())
	{
		$arAdminTasks = array_values(CGroup::GetTasks($groupAdmin));
		$arAdminTasks[] = $arTask["ID"];
		CGroup::SetTasks($groupAdmin, $arAdminTasks);
	}
}

$allowGuests = COption::GetOptionString("main", "wizard_allow_group", "N", WIZARD_SITE_ID);
if($allowGuests == "Y" && !WIZARD_IS_INSTALLED)
{
	$dbResult = CGroup::GetList('', '', Array("STRING_ID_EXACT_MATCH" => "Y"));
	while ($arExistsGroup = $dbResult->Fetch())
	{
		if($arExistsGroup["ID"] != 1 && $arExistsGroup["ID"] !=2)
		{
			if(!in_array($arExistsGroup["STRING_ID"], $SiteGroup["STRING_ID"]))
			{
				$allowGuests = COption::GetOptionString("main", "wizard_allow_group", "N", $site_id);
				WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array($arExistsGroup["ID"] => "D"));
			}
		}
	}
}
if (!WIZARD_IS_INSTALLED)
{
	WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR."oauth/"), Array("2" => 'R'));
	WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR."rest/"), Array("2" => 'R'));
}

// Add groups for crm shop
$groupObject = new CGroup;
$groupsData = array(
	array(
		"~ID" => "15",
		"ACTIVE" => "Y",
		"C_SORT" => 100,
		"NAME" => GetMessage("SALE_USER_GROUP_SHOP_ADMIN_NAME"),
		"STRING_ID" => "CRM_SHOP_ADMIN",
		"DESCRIPTION" => GetMessage("SALE_USER_GROUP_SHOP_ADMIN_DESC"),
		"BASE_RIGHTS" => array("sale" => "W"),
		"TASK_RIGHTS" => array("catalog" => "W", "main" => "R", "iblock" => "X")
	),
	array(
		"~ID" => "16",
		"ACTIVE" => "Y",
		"C_SORT" => 100,
		"NAME" => GetMessage("SALE_USER_GROUP_SHOP_MANAGER_NAME"),
		"STRING_ID" => "CRM_SHOP_MANAGER",
		"DESCRIPTION" => GetMessage("SALE_USER_GROUP_SHOP_MANAGER_DESC"),
		"BASE_RIGHTS" => array("sale" => "U"),
		"TASK_RIGHTS" => array("catalog" => "W", "iblock" => "W")
	),
);
global $APPLICATION;
$groupList = [];
foreach ($groupsData as $groupData)
{
	$groupId = $groupObject->add($groupData);
	if ($groupObject->LAST_ERROR == '' && $groupId)
	{
		$groupList[] = $groupId;
		foreach($groupData["BASE_RIGHTS"] as $moduleId => $letter)
		{
			$APPLICATION->setGroupRight($moduleId, $groupId, $letter, false);
		}
		foreach($groupData["TASK_RIGHTS"] as $moduleId => $letter)
		{
			switch ($moduleId)
			{
				case "iblock":
					if (CModule::IncludeModule("iblock"))
					{
						CIBlockRights::setGroupRight($groupId, "CRM_PRODUCT_CATALOG", $letter);
					}
					break;
				default:
					CGroup::SetModulePermission($groupId, $moduleId, CTask::GetIdByLetter($letter, $moduleId));
			}
		}
	}
}

$group = new CGroup;
$group->Update(2, array(
	'SECURITY_POLICY' => serialize(array('LOGIN_ATTEMPTS' => 12))
));

// Admin is portal administrator
$groupList[] = 12;
CUser::AppendUserGroup(1, $groupList);
?>

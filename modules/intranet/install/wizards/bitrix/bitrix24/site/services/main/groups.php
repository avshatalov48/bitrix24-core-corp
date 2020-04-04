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
	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => $arGroup["STRING_ID"], "STRING_ID_EXACT_MATCH" => "Y"));
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
		CGroup::SetTasks($groupID, $arTasksID, true);

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
		
	if (WIZARD_IS_RERUN === false)
	{
		if ($arGroup["STRING_ID"] == "EMPLOYEES_".WIZARD_SITE_ID)
		{
			COption::SetOptionString("main", "new_user_registration_def_group", $groupID);

		}

	}
}


$dbGroupUsers = CGroup::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
$arGroupsId = Array("ADMIN_SECTION", "SUPPORT", "CREATE_GROUPS", "PERSONNEL_DEPARTMENT", "DIRECTION", "MARKETING_AND_SALES");
while($arGroupUser = $dbGroupUsers->Fetch())
{
	if(in_array($arGroupUser["STRING_ID"], $arGroupsId))
	{
		define("WIZARD_".$arGroupUser["STRING_ID"]."_GROUP", $arGroupUser["ID"]);
	}
	else
	{
		if(substr($arGroupUser["STRING_ID"], -2) == WIZARD_SITE_ID)
			define("WIZARD_".substr($arGroupUser["STRING_ID"], 0, -3)."_GROUP", $arGroupUser["ID"]);
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

$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "EMPLOYEES_".WIZARD_SITE_ID, "STRING_ID_EXACT_MATCH" => "Y"));
if ($arExistsGroup = $dbResult->Fetch())
	$groupID = $arExistsGroup["ID"];
	
if($groupID && WIZARD_SITE_DEPARTAMENT && CModule::IncludeModule("iblock"))
{

	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments", "TYPE" => "structure"));
	$iblockID = false; 
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	
		$arFilter["ID"] = WIZARD_SITE_DEPARTAMENT;
		$rsSections = CIBlockSection::GetList(array(), $arFilter);
		$arSection = $rsSections->GetNext();
		
		$arFilter = array (
			"LEFT_MARGIN" => $arSection["LEFT_MARGIN"],
			"RIGHT_MARGIN" => $arSection["RIGHT_MARGIN"],
			"BLOCK_ID" => $iblockID,
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
		);
				
		$rsSections = CIBlockSection::GetList(array("left_margin"=>"asc"), $arFilter);
		$arSectionUsers = array();
		while($arSection = $rsSections->GetNext())
		{
			$arSectionUsers[] =  $arSection['ID'];
			
		}

		$rsUsers = CUser::GetList(($by="id"), ($order="asc"), array("UF_DEPARTMENT" => $arSectionUsers));
		while($arUsers = $rsUsers->Fetch())
		{
			CUser::AppendUserGroup($arUsers["ID"], $groupID);
		}
	}
	
	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "PERSONNEL_DEPARTMENT", "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
	{
		$groupID = $arExistsGroup["ID"];
		$arSubordinateGroups = CGroup::GetSubordinateGroups($groupID);
		$arSubordinateGroups[] = $SiteGroups["EMPLOYEES_".WIZARD_SITE_ID];
		CGroup::SetSubordinateGroups($groupID, $arSubordinateGroups);
	}
	
	CGroup::SetSubordinateGroups($SiteGroups["PORTAL_ADMINISTRATION_".WIZARD_SITE_ID], Array($SiteGroups["EMPLOYEES_".WIZARD_SITE_ID]));
}

$allowGuests = COption::GetOptionString("main", "wizard_allow_group", "N", WIZARD_SITE_ID);
if($allowGuests == "Y" && !WIZARD_IS_INSTALLED)
{
	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID_EXACT_MATCH" => "Y"));
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

$group = new CGroup;
$group->Update(2, array(
	'SECURITY_POLICY' => serialize(array('LOGIN_ATTEMPTS' => 12))
));

// Admin is portal administrator
CUser::AppendUserGroup(1, 12);
?>

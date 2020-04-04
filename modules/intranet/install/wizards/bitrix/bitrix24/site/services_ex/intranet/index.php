<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("intranet"))
	return;

COption::SetOptionString("intranet", "search_user_url", WIZARD_SITE_DIR."contacts/personal/user/#ID#/", false, WIZARD_SITE_ID);

$arIblockCode = Array(
	"iblock_absence" => "absence_extranet",
);

foreach ($arIblockCode as $option => $iblockCode)
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => "structure", "SITE_ID" => WIZARD_SITE_ID));
	if ($arIBlock = $rsIBlock->Fetch())
		COption::SetOptionString("intranet", $option, $arIBlock["ID"], false, WIZARD_SITE_ID);
}

$rsIBlock = CIBlock::GetList(array(), array("CODE" => "calendar_users_extranet", "TYPE" => "events", "SITE_ID" => WIZARD_SITE_ID));
if ($arIBlock = $rsIBlock->Fetch())
	COption::SetOptionString("intranet", "iblock_calendar",  $arIBlock["ID"], false, WIZARD_SITE_ID);

$rsIBlock = CIBlock::GetList(array(), array("CODE" => "extranet_tasks", "TYPE" => "services", "SITE_ID" => WIZARD_SITE_ID));
if ($arIBlock = $rsIBlock->Fetch())
	COption::SetOptionString("intranet", "iblock_tasks",  $arIBlock["ID"], false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_user', WIZARD_SITE_DIR.'contacts/personal/user/#USER_ID#/', false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_task_user', WIZARD_SITE_DIR.'contacts/personal/user/#USER_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_user_entry', WIZARD_SITE_DIR.'contacts/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group_entry', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);
?>

<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (WIZARD_IS_RERUN === true)
	return;

CGroup::SetSubordinateGroups(WIZARD_EXTRANET_ADMIN_GROUP, Array(WIZARD_EXTRANET_GROUP));

SetMenuTypes(Array("left" => GetMessage("MAIN_OPT_MENU_SECT"), "top" => GetMessage("MAIN_OPT_MENU_MAIN")),WIZARD_SITE_ID);

$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:8:"ADV@9058";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:23:"EXTRANET_CONTACTS@11468";a:5:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:2:{s:25:"MY_WORKGROUPS_USERS_COUNT";s:1:"5";s:18:"PUBLIC_USERS_COUNT";s:1:"5";}}s:15:"WORKGROUPS@2647";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:11:"TASKS@27413";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"UPDATES@32753";a:5:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:2:{s:11:"ENTITY_TYPE";s:1:"G";s:8:"EVENT_ID";s:0:"";}}s:14:"MESSAGES@24748";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"PROFILE@20859";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"TICKETS@11871";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:15:"RSSREADER@16757";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';

$arOptions = unserialize($sOptions);
CExtranetWizardServices::SetUserOption('intranet', '~gadgets_dashboard_external', $arOptions, $common = true);

COption::SetOptionString("tasks", "paths_task_user", WIZARD_SITE_DIR."contacts/personal/user/#user_id#/tasks/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_user_entry", WIZARD_SITE_DIR."contacts/personal/user/#user_id#/tasks/task/view/#task_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_user_edit", WIZARD_SITE_DIR."contacts/personal/user/#user_id#/tasks/task/edit/#task_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_user_action", WIZARD_SITE_DIR."contacts/personal/user/#user_id#/tasks/task/#action#/#task_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_group", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_group_entry", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/view/#task_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_group_edit", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/edit/#task_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("tasks", "paths_task_group_action", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/#action#/#task_id#/", false, WIZARD_SITE_ID);

$arSites = array();
$arSitesID = array();
$rsSites = CSite::GetList($by="sort", $order="desc", array());
while ($arSite = $rsSites->Fetch())
{
	$arSites[] = $arSite;
	$arSitesID[] = $arSite["ID"];
}

COption::SetOptionString('calendar', 'pathes_for_sites', false);
COption::SetOptionString("calendar", 'pathes_sites', serialize($arSitesID));

foreach($arSites as $arSite)
{
	$bExtranet = ($arSite["ID"] == WIZARD_SITE_ID);
	$folder = ($bExtranet ? "contacts" : "company");

	COption::SetOptionString("calendar", "pathes_".$arSite["ID"], serialize(array(
		"path_to_user" => $arSite["DIR"].$folder."/personal/user/#user_id#/",
		"path_to_user_calendar"	=> $arSite["DIR"].$folder."/personal/user/#user_id#/calendar/",
		"path_to_group" => $arSite["DIR"]."workgroups/group/#group_id#/",
		"path_to_group_calendar" => $arSite["DIR"]."workgroups/group/#group_id#/calendar/",
		"path_to_vr" => "",
		"path_to_rm" => ""
	)));
}
?>
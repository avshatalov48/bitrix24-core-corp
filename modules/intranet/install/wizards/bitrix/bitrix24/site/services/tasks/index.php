<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("tasks"))
	return;

COption::SetOptionString('tasks', 'path_task_user', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/', false, $site_id);
COption::SetOptionString('tasks', 'path_task_user_entry',  WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', false, $site_id);
COption::SetOptionString('tasks', 'path_task_group',  WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/', false, $site_id);
COption::SetOptionString('tasks', 'path_task_group_entry',  WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/', false, $site_id);
COption::SetOptionString("tasks", "task_comment_allow_edit", 1);

// for new portals disable gantt hint, show demo-data instead
\Bitrix\Main\Config\Option::set('tasks', 'task_list_enable_gantt_hint', 'N');

// create default task template for each new user
\Bitrix\Intranet\Integration\Tasks::createDemoTemplates();

// create demo data for the portal owner
\Bitrix\Intranet\Integration\Tasks::createDemoTasksForUser(1);
?>
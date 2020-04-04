<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Internals\Counter;

$arResult['BX24_RU_ZONE'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') &&
							preg_match("/^(ru)_/", COption::GetOptionString("main", "~controller_group_name", ""));

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
//$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if ($helper->checkHasFatals())
{
	return;
}

$arResult['TEMPLATE_DATA'] = array(// contains data generated in result_modifier.php
);
$arResult['JS_DATA'] = array(
	// everything you put here, will be accessible inside js controller through this.option('keyName')
	'filterId' => $arParams['FILTER_ID'],
	'use_ajax_filter' => $arParams['USE_AJAX_ROLE_FILTER'] == 'Y',
	'text_sl_effective'=>GetMessage('TASKS_PANEL_TEXT_EFFECTIVE'),
	'show_sl_effective'=> !CUserOptions::GetOption('spotlight', 'view_date_tasks_sl_effective', false)
);

$arResult['ITEMS'] = array();

$urlRoleTemplate = $arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS'] : $arParams['PATH_TO_USER_TASKS'];
$tasksLink = CComponentEngine::makePathFromTemplate(
	$urlRoleTemplate,
	array(
		'group_id' => $arParams['GROUP_ID'],
		'user_id' => $arParams['USER_ID']
	)
);

$arResult['ITEMS'][] = array(
	"TEXT" => GetMessage("TASKS_PANEL_TAB_ALL"),
	"URL" => $tasksLink.'?F_CANCEL=Y&F_SECTION=ADVANCED&clear_filter=Y&apply_filter=Y',
	"ID" => "view_all",
	'CLASS' => 'tasks_role_link',
	'SUB_LINK' => array(
		'CLASS' => '',
		'URL' => CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_TASK'],
			array(
				'action' => 'edit',
				'task_id' => 0,
				'user_id' => $arParams['USER_ID'],
				'group_id' => $arParams['GROUP_ID']
			)
		)
	),
	"IS_ACTIVE" => ($arParams["MARK_SECTION_KANBAN"] != "Y" &&
					$arParams["MARK_TEMPLATES"] != "Y" &&
					$arParams["MARK_SECTION_EFFECTIVE"] != "Y" &&
					$arParams["MARK_SECTION_PROJECTS"] != "Y" &&
					$arParams["MARK_SECTION_MANAGE"] != "Y" &&
					$arParams["MARK_SECTION_EMPLOYEE_PLAN"] != "Y" &&
					$arParams["MARK_RECYCLEBIN"] != "Y" &&
					$arParams["MARK_SECTION_REPORTS"] != "Y") &&
				   ($arParams['DEFAULT_ROLEID'] == 'view_all' || $arParams['DEFAULT_ROLEID'] == ''), // need refactoring
	'COUNTER' => $arResult['TOTAL'],
	'COUNTER_ID' => Counter\Name::TOTAL,
	'COUNTER_ACTIVE' => 'Y'
);

// base items
foreach ($arResult['ROLES'] as $roleId => $role)
{
	$arResult['ITEMS'][] = array(
		'TEXT' => $role['TEXT'],
		'URL' => $tasksLink.'?'.$role['HREF'].'&clear_filter=Y',
		'ON_CLICK' => '',
		//		'ON_CLICK'=>$arParams['USE_AJAX_ROLE_FILTER'] == 'N'
		//			? null
		//			: 'BX.Tasks.Component.TopMenu.getInstance("topmenu").filter("'.strtolower($roleId).'")',
		'ID' => strtolower($roleId),
		'CLASS' => 'tasks_role_link',
		'IS_ACTIVE' => ($arParams['MARK_ACTIVE_ROLE'] == 'Y' && $role['IS_ACTIVE']) ||
					   $arParams['DEFAULT_ROLEID'] == strtolower($roleId), // need refactoring
		'COUNTER' => $role['COUNTER'],
		'COUNTER_ID' => $role['COUNTER_ID']
	);
}

if ($arParams['SHOW_SECTION_PROJECTS'] == 'Y')
{
	$createGroupLink = CComponentEngine::makePathFromTemplate(
		$arParams['TASKS_GROUP_CREATE_URL_TEMPLATE'],
		array(
			'user_id' => $arParams['LOGGED_USER_ID']
		)
	);

	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_PROJECTS"),
		"URL" => $tasksLink.'projects/',
		"ID" => "view_projects",
		"IS_ACTIVE" => $arParams["MARK_SECTION_PROJECTS"] === "Y",
		'SUB_LINK' => array('CLASS' => '', 'URL' => $createGroupLink),
	);
}

$arResult['ITEMS'][] = array(
	"TEXT" => GetMessage("TASKS_PANEL_TAB_KANBAN"),
	"URL" => $tasksLink.'board/',
	"ID" => "view_kanban",
	"IS_ACTIVE" => $arParams["MARK_SECTION_KANBAN"] == "Y",
);

if ($arParams["SHOW_SECTION_MANAGE"] != "N")
{
	$counter = intval($arResult["SECTION_MANAGE_COUNTER"]);
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_MANAGE"),
		"URL" => $tasksLink.'departments/',
		"ID" => "view_departments",
		'COUNTER' => $counter,
		'COUNTER_ID' => 'departments_counter',
		"IS_ACTIVE" => $arParams["MARK_SECTION_MANAGE"] === "Y",
	);
}

	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_EFFECTIVE"),
		"URL" => $tasksLink.'effective/',
		"ID" => "view_effective",
		'MAX_COUNTER_SIZE'=>100,
		"IS_ACTIVE" => $arParams["MARK_SECTION_EFFECTIVE"] == "Y",
		'COUNTER' => (int)$arResult['EFFECTIVE_COUNTER']
	);


if (!\Bitrix\Tasks\Integration\Extranet\User::isExtranet() && !$arParams['GROUP_ID'])
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_EMPLOYEE_PLAN"),
		"URL" => $tasksLink.'employee/plan/',
		"ID" => "view_employee_plan",
		"IS_ACTIVE" => $arParams["MARK_SECTION_EMPLOYEE_PLAN"] == "Y",
		'IS_DISABLED' => true
	);
}

if ($arParams["SHOW_SECTION_REPORTS"] == "Y")
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_REPORTS"),
		"URL" => $tasksLink.'report/',
		"ID" => "view_reports",
		"IS_ACTIVE" => $arParams["MARK_SECTION_REPORTS"] === "Y",
		'IS_DISABLED' => true
	);
}

if ($arResult["BX24_RU_ZONE"])
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_APPLICATIONS"),
		"URL" => "/marketplace/category/tasks/",
		"ID" => "view_apps",
	);
}

if ($arParams["SHOW_SECTION_TEMPLATES"] == "Y")
{
	$arResult['ITEMS'][] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_TEMPLATES"),
		"URL" => $tasksLink.'templates/',
		"ID" => "view_templates",
		"IS_ACTIVE" => $arParams["MARK_TEMPLATES"] == "Y",
		'IS_DISABLED' => true
	);
}
if ($arParams["SHOW_SECTION_RECYCLEBIN"] != "N" && CModule::includeModule('recyclebin'))
{
	$arResult['ITEMS'][] = array(
		"TEXT"      => GetMessage("TASKS_PANEL_TAB_RECYCLEBIN"),
		"URL"       => $tasksLink.'recyclebin/',
		"ID"        => "view_recyclebin",
		"IS_ACTIVE" => $arParams["MARK_RECYCLEBIN"] == "Y",
		//		'IS_DISABLED' => true
	);
}
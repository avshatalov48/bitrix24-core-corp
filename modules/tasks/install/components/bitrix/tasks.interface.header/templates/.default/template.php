<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;

$APPLICATION->SetAdditionalCSS("template_styles.css");

$isCollab = isset($arParams['CONTEXT']) && $arParams['CONTEXT'] === Context::getCollab();
if (!isset($arParams['MENU_GROUP_ID']))
{
	$arParams['MENU_GROUP_ID'] = $arParams['GROUP_ID'];
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.topmenu',
		'',
		[
			'GRID_ID' => $arParams['GRID_ID'],
			'FILTER_ID' => $arParams['FILTER_ID'],
			'USER_ID' => $arParams['USER_ID'],
			'GROUP_ID' => $arParams['MENU_GROUP_ID'],
			'PROJECT_VIEW' => $arParams['PROJECT_VIEW'],
			'SECTION_URL_PREFIX' => '',

			'USE_AJAX_ROLE_FILTER' => $arParams['USE_AJAX_ROLE_FILTER'],
			'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'] ?? null,
			'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'] ?? null,
			'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'] ?? null,
			'MARK_TEMPLATES' => $arParams['MARK_TEMPLATES'] ?? null,
			'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'] ?? null,

			'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
			'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
			'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
			'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

			'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
			'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
			'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
			'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

			'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
			'DEFAULT_ROLEID' => $arParams['DEFAULT_ROLEID'],
			'SCOPE' => $arParams['SCOPE'],
		],
		$component,
		[
			'HIDE_ICONS' => true
		]
	);

$showQuickForm = $arParams['SHOW_QUICK_FORM'] === "Y" && \Bitrix\Tasks\Util\Restriction::canManageTask();

if ($isCollab)
{
	$showQuickForm = false;
}

if ($arParams['SHOW_FILTER'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.filter',
		'',
		array(
			'FILTER_ID' => $arParams[ "FILTER_ID" ] ?? null,
			'GRID_ID' => $arParams[ "GRID_ID" ] ?? null,
			'FILTER' => $arParams[ 'FILTER' ] ?? null,
			'PRESETS' => $arParams[ 'PRESETS' ] ?? null,

			'TEMPLATES_LIST' => $arParams[ 'TEMPLATES_LIST' ] ?? null,

			'USER_ID' => $arParams[ 'USER_ID' ] ?? null,
			'GROUP_ID' => $arParams[ 'GROUP_ID' ] ?? null,
			'SPRINT_ID' => $arParams[ 'SPRINT_ID' ] ?? null,
			'MENU_GROUP_ID' => $arParams['MENU_GROUP_ID'] ?? null,

			'USE_LIVE_SEARCH'=>$arParams['USE_LIVE_SEARCH'] ?? null,

			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ] ?? null,
			'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ] ?? null,
			'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ] ?? null,
			'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ] ?? null,

			'SHOW_USER_SORT'=>$arParams['SHOW_USER_SORT'] ?? null,
			'USE_GROUP_SELECTOR' => $arParams['USE_GROUP_SELECTOR'] ?? null,

			'USE_EXPORT' =>$arParams['USE_EXPORT'] ?? null,
			'USE_GROUP_BY_SUBTASKS' => $arParams['USE_GROUP_BY_SUBTASKS'] ?? null,
			'USE_GROUP_BY_GROUPS' => $arParams['USE_GROUP_BY_GROUPS'] ?? null,
			'GROUP_BY_PROJECT' => $arParams['GROUP_BY_PROJECT'] ?? null,
			'SHOW_QUICK_FORM_BUTTON'=>$showQuickForm ? 'Y' : 'N',
			'POPUP_MENU_ITEMS'=>$arParams['POPUP_MENU_ITEMS'] ?? null,
			'SORT_FIELD'=>$arParams['SORT_FIELD'] ?? null,
			'SORT_FIELD_DIR'=>$arParams['SORT_FIELD_DIR'] ?? null,

			'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,
			'SCOPE' => $arParams['SCOPE'] ?? null,
			'CONTEXT' => $arParams['CONTEXT'] ?? null,
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

?>

<?php
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.toolbar',
		'',
		array(
			'USER_ID' => $arParams[ 'USER_ID' ] ?? null,
			'GROUP_ID' => $arParams[ 'GROUP_ID' ] ?? null,

			'GRID_ID' => $arParams['GRID_ID'] ?? null,
			'FILTER_ID' => $arParams[ "FILTER_ID" ] ?? null,

			'SHOW_VIEW_MODE'=>$arParams['SHOW_VIEW_MODE'] ?? null,

			'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ] ?? null,
			'PATH_TO_GROUP_TASKS' => $arParams[ 'PATH_TO_GROUP_TASKS' ] ?? null,
			'DEFAULT_ROLEID' => $arParams[ 'DEFAULT_ROLEID' ] ?? null,

			'SHOW_TOOLBAR'=>
				(isset($arParams['MARK_SPECIAL_PRESET']) && $arParams['MARK_SPECIAL_PRESET'] === 'Y')
				|| (isset($arParams['MARK_SECTION_ALL']) && $arParams['MARK_SECTION_ALL'] === 'Y')
					? 'N' : 'Y',
			'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,
			'VIEW_MODE_LIST' => (isset($arParams['PROJECT_VIEW']) && $arParams['PROJECT_VIEW'] === 'Y') ? ['VIEW_MODE_KANBAN', 'VIEW_MODE_LIST', 'VIEW_MODE_TIMELINE', 'VIEW_MODE_CALENDAR', 'VIEW_MODE_GANTT'] : [],
			'SCOPE' => $arParams['SCOPE'] ?? null,

			'SHOW_COUNTERS_TOOLBAR' => $arParams['SHOW_COUNTERS_TOOLBAR'] ?? null,
			'CONTEXT' => $arParams['CONTEXT'] ?? null,
		),
		$component,
		array('HIDE_ICONS' => true)
	);

?>

<?php

if ($arParams['SHOW_QUICK_FORM'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.quick.form',
		'',
		array(
			'FILTER_ID' => $arParams[ "FILTER_ID" ] ?? null,
			'GRID_ID' => $arParams[ "GRID_ID" ] ?? null,
			'FILTER' => $arParams[ 'FILTER' ] ?? null,

			'GET_LIST_PARAMS' => $arParams[ 'GET_LIST_PARAMS' ] ?? null,
			'COMPANY_WORKTIME' => $arParams[ 'COMPANY_WORKTIME' ] ?? null,
			'NAME_TEMPLATE' => $arParams[ 'NAME_TEMPLATE' ] ?? null,
			'USE_GROUP_BY_GROUPS' => $arParams['USE_GROUP_BY_GROUPS'] ?? null,
			'GROUP_BY_PROJECT' => $arParams['GROUP_BY_PROJECT'] ?? null,

			'USER_ID' => $arParams[ 'USER_ID' ] ?? null,
			'GROUP_ID' => $arParams[ 'GROUP_ID' ] ?? null,

			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ] ?? null,
			'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ] ?? null,
			'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ] ?? null,
			'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ] ?? null,

			'SCOPE' => $arParams['SCOPE'] ?? null,
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

?>

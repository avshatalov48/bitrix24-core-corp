<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetAdditionalCSS("template_styles.css");

if (!isset($arParams['MENU_GROUP_ID']))
{
	$arParams['MENU_GROUP_ID'] = $arParams['GROUP_ID'];
}
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'GRID_ID' => $arParams['GRID_ID'],
		'FILTER_ID' => $arParams['FILTER_ID'],
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['MENU_GROUP_ID'],
		'PROJECT_VIEW' => $arParams['PROJECT_VIEW'],
		'SECTION_URL_PREFIX' => '',

		'USE_AJAX_ROLE_FILTER' => $arParams['USE_AJAX_ROLE_FILTER'],
		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],
		'MARK_TEMPLATES' => $arParams['MARK_TEMPLATES'],
		'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'],

		'PATH_TO_GROUP_TASKS' => $arParams[ 'PATH_TO_GROUP_TASKS' ],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams[ 'PATH_TO_GROUP_TASKS_VIEW' ],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams[ 'PATH_TO_GROUP_TASKS_REPORT' ],

		'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ],
		'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ],
		'PATH_TO_USER_TASKS_VIEW' => $arParams[ 'PATH_TO_USER_TASKS_VIEW' ],
		'PATH_TO_USER_TASKS_REPORT' => $arParams[ 'PATH_TO_USER_TASKS_REPORT' ],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams[ 'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' ],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams[ 'PATH_TO_CONPANY_DEPARTMENT' ],
		'DEFAULT_ROLEID' => $arParams[ 'DEFAULT_ROLEID' ],
	),
	$component,
	array('HIDE_ICONS' => true)
);


$showQuickForm = $arParams['SHOW_QUICK_FORM'] === "Y" && \Bitrix\Tasks\Util\Restriction::canManageTask();

if ($arParams['SHOW_FILTER'] == 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.filter',
		'',
		array(
			'FILTER_ID' => $arParams[ "FILTER_ID" ],
			'GRID_ID' => $arParams[ "GRID_ID" ],
			'FILTER' => $arParams[ 'FILTER' ],
			'PRESETS' => $arParams[ 'PRESETS' ],

			'TEMPLATES_LIST' => $arParams[ 'TEMPLATES_LIST' ],

			'USER_ID' => $arParams[ 'USER_ID' ],
			'GROUP_ID' => $arParams[ 'GROUP_ID' ],
			'SPRINT_ID' => $arParams[ 'SPRINT_ID' ],
			'MENU_GROUP_ID' => $arParams['MENU_GROUP_ID'],

			'USE_LIVE_SEARCH'=>$arParams['USE_LIVE_SEARCH'],

			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ],
			'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ],
			'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ],
			'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ],

			'SHOW_USER_SORT'=>$arParams['SHOW_USER_SORT'],
			'USE_GROUP_SELECTOR' => $arParams['USE_GROUP_SELECTOR'],

			'USE_EXPORT' =>$arParams['USE_EXPORT'],
			'USE_GROUP_BY_SUBTASKS' => $arParams['USE_GROUP_BY_SUBTASKS'],
			'USE_GROUP_BY_GROUPS' => $arParams['USE_GROUP_BY_GROUPS'],
			'GROUP_BY_PROJECT' => $arParams['GROUP_BY_PROJECT'],
			'SHOW_QUICK_FORM_BUTTON'=>$showQuickForm ? 'Y' : 'N',
			'POPUP_MENU_ITEMS'=>$arParams['POPUP_MENU_ITEMS'],
			'SORT_FIELD'=>$arParams['SORT_FIELD'],
			'SORT_FIELD_DIR'=>$arParams['SORT_FIELD_DIR'],

			'PROJECT_VIEW' => $arParams['PROJECT_VIEW']
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.toolbar',
	'',
	array(
		'USER_ID' => $arParams[ 'USER_ID' ],
		'GROUP_ID' => $arParams[ 'GROUP_ID' ],
		'SPRINT_ID' => $arParams[ 'SPRINT_ID' ],
		'SPRINT_SELECTED' => $arParams[ 'SPRINT_SELECTED' ],

		'GRID_ID' => $arParams['GRID_ID'],
		'FILTER_ID' => $arParams[ "FILTER_ID" ],

		'SHOW_VIEW_MODE'=>$arParams['SHOW_VIEW_MODE'],

		'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ],
		'PATH_TO_GROUP_TASKS' => $arParams[ 'PATH_TO_GROUP_TASKS' ],
		'DEFAULT_ROLEID' => $arParams[ 'DEFAULT_ROLEID' ],

		'SHOW_TOOLBAR'=>$arParams['MARK_SPECIAL_PRESET']=='Y' || $arParams['MARK_SECTION_ALL']=='Y' ? 'N' : 'Y',
		'PROJECT_VIEW' => $arParams['PROJECT_VIEW'],
		'VIEW_MODE_LIST' => $arParams['PROJECT_VIEW'] == 'Y' ? ['VIEW_MODE_KANBAN', 'VIEW_MODE_LIST', 'VIEW_MODE_TIMELINE', 'VIEW_MODE_CALENDAR', 'VIEW_MODE_GANTT', 'VIEW_MODE_SPRINT'] : [],
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>

<?php

if ($arParams['SHOW_QUICK_FORM'] == 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.quick.form',
		'',
		array(
			'FILTER_ID' => $arParams[ "FILTER_ID" ],
			'GRID_ID' => $arParams[ "GRID_ID" ],
			'FILTER' => $arParams[ 'FILTER' ],

			'GET_LIST_PARAMS' => $arParams[ 'GET_LIST_PARAMS' ],
			'COMPANY_WORKTIME' => $arParams[ 'COMPANY_WORKTIME' ],
			'GANTT_MODE' => isset($arParams[ 'GANTT_MODE' ]) ? $arParams[ 'GANTT_MODE' ] : "",
			'NAME_TEMPLATE' => $arParams[ 'NAME_TEMPLATE' ],
			'USE_GROUP_BY_GROUPS' => $arParams['USE_GROUP_BY_GROUPS'],
			'GROUP_BY_PROJECT' => $arParams['GROUP_BY_PROJECT'],

			'USER_ID' => $arParams[ 'USER_ID' ],
			'GROUP_ID' => $arParams[ 'GROUP_ID' ],

			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams[ 'PATH_TO_USER_TASKS_TEMPLATES' ],
			'PATH_TO_USER_TASKS' => $arParams[ 'PATH_TO_USER_TASKS' ],
			'PATH_TO_USER_TASKS_TASK' => $arParams[ 'PATH_TO_USER_TASKS_TASK' ],
			'PATH_TO_GROUP_TASKS_TASK' => $arParams[ 'PATH_TO_GROUP_TASKS_TASK' ],

		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

?>
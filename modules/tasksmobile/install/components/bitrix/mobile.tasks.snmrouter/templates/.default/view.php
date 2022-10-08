<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

$APPLICATION->IncludeComponent(
	'bitrix:tasks.task',
	(isset($arParams['NEW_CARD']) && $arParams['NEW_CARD'] === 'Y' ? 'view_new_v2' : 'view'),
	[
		'ID' => $arParams['TASK_ID'],
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => '',
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TEMPLATE_TO_USER_PROFILE'],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_TEMPLATES' => '',
		'PATH_TO_USER_TEMPLATES_TEMPLATE' => '',
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_EDIT' => $arParams['PATH_TO_USER_TASKS_EDIT'],
		'PATH_TEMPLATE_TO_USER_PROFILE' => $arParams['PATH_TEMPLATE_TO_USER_PROFILE'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => '',
		'PATH_TO_GROUP' => '',
		'PATH_TO_GROUP_TASKS' => '',

		'SET_NAV_CHAIN' => 'N',
		'SET_TITLE' => 'N',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'DATE_TIME_FORMAT' => $arParams['DATE_TIME_FORMAT'],
		'GUID' => $arParams['GUID'],

		'SHOW_RATING' => 'Y',
		'RATING_TYPE' => 'like',

		'SUB_ENTITY_SELECT' => [
			'TAG',
			'CHECKLIST',
			'REMINDER',
			'PROJECTDEPENDENCE',
			'TEMPLATE',
			'LOG',
			'ELAPSEDTIME',
			'TIMEMANAGER',
		],
		'AUX_DATA_SELECT' => [
			'COMPANY_WORKTIME',
			'USER_FIELDS',
			'TEMPLATE',
		],

		'ACTION' => 'view',
		'PLATFORM' => 'mobile',
	],
	$this->__component,
	['HIDE_ICONS' => 'Y']
);
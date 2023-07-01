<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],

		'GROUP_ID'           => $arParams['MENU_GROUP_ID'],
		'SECTION_URL_PREFIX' => '',

		'MARK_TRASH' => 'Y',

		'PATH_TO_GROUP_TASKS'        => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK'   => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW'   => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

		'PATH_TO_USER_TASKS'                   => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK'              => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW'              => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT'            => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES'         => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT']
	],
	$component,
	['HIDE_ICONS' => true]
);
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:trash.list',
	".default",
	[
		"MODULE_ID"            => "tasks",
		"USER_ID"              => \Bitrix\Tasks\Util\User::getId(),
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
	],
	$component,
	["HIDE_ICONS" => "Y"]
); ?>
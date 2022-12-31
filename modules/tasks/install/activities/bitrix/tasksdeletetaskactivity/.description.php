<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_DTA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('TASKS_DTA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksDeleteTaskActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'tasks',
		'OWN_NAME' => Loc::getMessage('TASKS_DTA_CATEGORY'),
	],
	'FILTER' => [
		'INCLUDE' => [
			['tasks'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['taskManagement'],
	],
];
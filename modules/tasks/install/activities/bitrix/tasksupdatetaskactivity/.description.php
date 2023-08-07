<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_UTA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('TASKS_UTA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksUpdateTaskActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'tasks',
		'OWN_NAME' => Loc::getMessage('TASKS_UTA_CATEGORY'),
	],
	'RETURN' => [
		'ErrorMessage' => [
			'NAME' => GetMessage('TASKS_UTA_ERROR_MESSAGE'),
			'TYPE' => 'string',
		],
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
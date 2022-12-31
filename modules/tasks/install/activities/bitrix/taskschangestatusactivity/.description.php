<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_CHANGE_STATUS_NAME'),
	'DESCRIPTION' => Loc::getMessage('TASKS_CHANGE_STATUS_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksChangeStatusActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'tasks',
		'OWN_NAME' => Loc::getMessage('TASKS_CHANGE_STATUS_CATEGORY'),
	],
	'FILTER' => [
		'INCLUDE' => [
			['tasks'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['elementControl'],
	],
];
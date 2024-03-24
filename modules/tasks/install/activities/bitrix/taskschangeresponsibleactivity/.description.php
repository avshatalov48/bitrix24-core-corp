<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_CHANGE_RESPONSIBLE_NAME_1_V2'),
	'DESCRIPTION' => Loc::getMessage('TASKS_CHANGE_RESPONSIBLE_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksChangeResponsibleActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'tasks',
		'OWN_NAME' => Loc::getMessage('TASKS_CHANGE_RESPONSIBLE_CATEGORY'),
	],
	'FILTER' => [
		'INCLUDE' => [
			['tasks'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['elementControl'],
	],
];
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_CHANGE_STAGE_NAME'),
	'DESCRIPTION' => Loc::getMessage('TASKS_CHANGE_STAGE_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksChangeStageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'tasks',
		'OWN_NAME' => Loc::getMessage('TASKS_CHANGE_STAGE_CATEGORY'),
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

if (
	isset($documentType)
	&& $documentType[0] === 'tasks'
	&& mb_strpos($documentType[2], 'TASK_USER_') === 0
)
{
	$arActivityDescription['EXCLUDED'] = true;
}
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('TASKS_GLDA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('TASKS_GLDA_DESC_1_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksGetDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
	],
	'ADDITIONAL_RESULT' => ['FieldsMap'],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['taskManagement'],
		'SORT' => 2200,
		'IS_SUPPORTING_ROBOT' => true,
	],
];
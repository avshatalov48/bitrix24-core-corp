<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('TASKS_GLDA_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_GLDA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'TasksGetDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
	],
	'ADDITIONAL_RESULT' => ['FieldsMap'],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
	],
];

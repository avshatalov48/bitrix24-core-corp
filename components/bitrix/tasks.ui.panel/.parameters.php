<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!\Bitrix\Main\Loader::includeModule('tasks'))
{
	return;
}

$arComponentParameters = Array(
	'PARAMETERS' => Array(
		'USER_ID' => Array(
			'NAME' => GetMessage('INTL_USER_ID'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
		),
		'GROUP_ID' => Array(
			'NAME' => GetMessage('INTL_GROUP_ID'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
		),
		'NAVIGATION_BAR_ACTIVE' => Array(
			'NAME' => GetMessage('INTL_NAVIGATION_BAR_ACTIVE'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
	)
);

$settCodes = array('TASK', 'LIST', 'KANBAN', 'GANTT', 'WIDGET', 'REPORT', 'TEMPLATES', 'PROJECTS', 'PLAN');
foreach ($settCodes as $code)
{
	$arComponentParameters['PARAMETERS']['PATH_TO_TASKS_' . $code] = Array(
			'NAME' => GetMessage('INTL_PATH_TO_TASKS_' . $code),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		);
}

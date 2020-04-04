<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$arComponentParameters = Array(
	'PARAMETERS' => Array(
		'PERSONAL' => Array(
			'NAME' => GetMessage('INTL_PERSONAL'),
			'TYPE' => 'CHECKBOX',
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'USER_ID' => Array(
			'NAME' => GetMessage('INTL_USER_ID'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'COLS' => 3,
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'GROUP_ID' => Array(
			'NAME' => GetMessage('INTL_GROUP_ID'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'COLS' => 3,
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'PATH_TO_GROUP_TASKS' => Array(
			'NAME' => GetMessage('INTL_PATH_TO_GROUP_TASKS'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
		'PATH_TO_GROUP_TASKS_TASK' => Array(
			'NAME' => GetMessage('INTL_PATH_TO_GROUP_TASKS_TASK'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
		'PATH_TO_USER_TASKS' => Array(
			'NAME' => GetMessage('INTL_PATH_TO_USER_TASKS'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
		'PATH_TO_USER_TASKS_TASK' => Array(
			'NAME' => GetMessage('INTL_PATH_TO_USER_TASKS_TASK'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
		'PATH_TO_USER_PROFILE' => Array(
			'NAME' => GetMessage('INTL_PATH_TO_USER_PROFILE'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'COLS' => 25,
			'PARENT' => 'URL_TEMPLATES',
		),
		'ITEMS_COUNT' => Array(
			'NAME' => GetMessage('INTL_ITEM_COUNT'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '20',
			'COLS' => 3,
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTL_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'DEFAULT' => '',
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'PREVIEW_WIDTH' => array(
			'TYPE' => 'STRING',
			'NAME' => GetMessage('INTL_PREVIEW_WIDTH'),
			'DEFAULT' => '200',
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'PREVIEW_HEIGHT' => array(
			'TYPE' => 'STRING',
			'NAME' => GetMessage('INTL_PREVIEW_HEIGHT'),
			'DEFAULT' => '150',
			'PARENT' => 'ADDITIONAL_SETTINGS',
		),
		'SET_TITLE' => Array()
	)
);
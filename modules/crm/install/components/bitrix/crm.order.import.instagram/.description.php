<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$arComponentDescription = [
	'NAME' => GetMessage('CRM_ORDER_IMPORT_INSTAGRAM_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ORDER_IMPORT_INSTAGRAM_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 10,
	'COMPLEX' => 'Y',
	'PATH' => [
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => [
			'ID' => 'order',
			'NAME' => GetMessage('CRM_ORDER_IMPORT_INSTAGRAM_NAME'),
		],
	],
	'CACHE_PATH' => 'Y',
];
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('controller'))
{
	return;
}

if (!ControllerIsSharedMode())
{
	return false;
}

$arComponentDescription = [
	'NAME' => GetMessage('CD_BCSC_NAME'),
	'DESCRIPTION' => GetMessage('CD_BCSC_DESCRIPTION'),
	'ICON' => '/images/1c-imp.gif',
	'CACHE_PATH' => 'Y',
	'SORT' => 120,
	'PATH' => [
		'ID' => 'service',
		'CHILD' => [
			'ID' => 'controller',
			'NAME' => GetMessage('CD_BCSC_CONTROLLER'),
			'SORT' => 30,
		],
	],
];

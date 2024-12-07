<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('controller'))
{
	return;
}

$arComponentDescription = [
	'NAME' => GetMessage('CD_BCSL_NAME'),
	'DESCRIPTION' => GetMessage('CD_BCSL_DESCRIPTION'),
	'ICON' => '/images/news_list.gif',
	'CACHE_PATH' => 'Y',
	'SORT' => 140,
	'PATH' => [
		'ID' => 'service',
		'CHILD' => [
			'ID' => 'controller',
			'NAME' => GetMessage('CD_BCSL_CONTROLLER'),
			'SORT' => 30,
		],
	],
];

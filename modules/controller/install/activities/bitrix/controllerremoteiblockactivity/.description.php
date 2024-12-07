<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('BPCRIA_DESCR_NAME'),
	'DESCRIPTION' => GetMessage('BPCRIA_DESCR_DESCR'),
	'TYPE' => 'activity',
	'CLASS' => 'ControllerRemoteIBlockActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
	],
	'FILTER' => [
		'INCLUDE' => [
			['iblock', 'CIBlockDocument'],
		]
	]
];

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [];
if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$settings = [
		'crmMode' => \Bitrix\Crm\Settings\Mode::getCurrentName()
	];
}

return [
	'css' => 'dist/analytics.bundle.css',
	'js' => 'dist/analytics.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => $settings,
];

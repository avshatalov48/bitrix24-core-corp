<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/currency-core.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'region' => \Bitrix\Main\Application::getInstance()->getLicense()->getRegion(),
	],
];

<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/department-control.bundle.css',
	'js' => 'dist/department-control.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'main.core.events',
	],
	'skip_core' => false,
];

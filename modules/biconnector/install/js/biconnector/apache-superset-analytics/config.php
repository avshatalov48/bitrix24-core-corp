<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('pull');

return [
	'js' => 'dist/apache-superset-analytics.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.analytics',
	],
	'skip_core' => true,
];

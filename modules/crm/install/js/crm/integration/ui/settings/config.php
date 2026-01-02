<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/settings.bundle.css',
	'js' => 'dist/settings.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'useAirDesign' => defined('AIR_SITE_TEMPLATE'),
	],
];

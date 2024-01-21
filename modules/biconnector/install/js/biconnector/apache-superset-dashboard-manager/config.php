<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/apache-superset-dashboard-manager.bundle.css',
	'js' => 'dist/apache-superset-dashboard-manager.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
		'main.popup',
		'ui.buttons',
		'ui.icon-set.main',
		'ui.design-tokens',
		'sidepanel',
	],
	'skip_core' => false,
];

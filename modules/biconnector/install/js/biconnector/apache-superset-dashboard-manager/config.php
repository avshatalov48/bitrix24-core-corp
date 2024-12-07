<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/apache-superset-dashboard-manager.bundle.css',
	'js' => 'dist/apache-superset-dashboard-manager.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.buttons',
		'main.core.events',
		'sidepanel',
		'biconnector.dashboard-export-master',
	],
	'skip_core' => false,
];

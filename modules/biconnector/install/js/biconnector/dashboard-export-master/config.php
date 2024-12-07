<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dashboard-export-master.bundle.css',
	'js' => 'dist/dashboard-export-master.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.loader',
		'main.popup',
		'biconnector.apache-superset-analytics',
		'biconnector.apache-superset-dashboard-manager',
		'ui.buttons',
		'ui.switcher',
		'ui.textcrop',
	],
	'skip_core' => false,
];

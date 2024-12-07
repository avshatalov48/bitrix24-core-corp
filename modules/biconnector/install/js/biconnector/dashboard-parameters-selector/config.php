<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dashboard-parameters-selector.bundle.css',
	'js' => 'dist/dashboard-parameters-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.notification',
		'ui.buttons',
		'main.popup',
	],
	'skip_core' => false,
];

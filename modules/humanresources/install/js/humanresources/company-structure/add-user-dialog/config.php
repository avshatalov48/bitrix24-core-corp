<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/add-user-dialog.bundle.css',
	'js' => 'dist/add-user-dialog.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.entity-selector',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.api',
		'humanresources.company-structure.utils',
		'ui.notification',
	],
	'skip_core' => false,
];

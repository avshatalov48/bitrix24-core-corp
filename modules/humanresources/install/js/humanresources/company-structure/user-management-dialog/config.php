<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-management-dialog.bundle.css',
	'js' => 'dist/user-management-dialog.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.entity-selector',
		'humanresources.company-structure.utils',
		'ui.notification',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.api',
	],
	'skip_core' => false,
];

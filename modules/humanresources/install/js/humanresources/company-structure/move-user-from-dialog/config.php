<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/move-user-from-dialog.bundle.css',
	'js' => 'dist/move-user-from-dialog.bundle.js',
	'rel' => [
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.utils',
		'main.core',
		'humanresources.company-structure.api',
		'ui.entity-selector',
		'ui.notification',
	],
	'skip_core' => false,
];

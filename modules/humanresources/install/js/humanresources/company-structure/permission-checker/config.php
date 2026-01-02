<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/checker.bundle.css',
	'js' => 'dist/checker.bundle.js',
	'rel' => [
		'humanresources.company-structure.api',
		'humanresources.company-structure.permission-checker',
		'main.core',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.utils',
	],
	'skip_core' => false,
];

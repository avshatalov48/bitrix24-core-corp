<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/checker.bundle.css',
	'js' => 'dist/checker.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'humanresources.company-structure.api',
		'humanresources.company-structure.chart-store',
	],
	'skip_core' => true,
];

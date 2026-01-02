<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/chart-store.bundle.css',
	'js' => 'dist/chart-store.bundle.js',
	'rel' => [
		'ui.vue3.pinia',
		'humanresources.company-structure.api',
		'humanresources.company-structure.chart-store',
		'main.core',
		'humanresources.company-structure.utils',
	],
	'skip_core' => false,
];
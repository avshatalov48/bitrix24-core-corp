<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/chart-wizard.bundle.css',
	'js' => 'dist/chart-wizard.bundle.js',
	'rel' => [
		'ui.vue3.pinia',
		'main.loader',
		'main.core',
		'humanresources.company-structure.permission-checker',
		'humanresources.company-structure.structure-components',
		'ui.icon-set.api.core',
		'ui.icon-set.crm',
		'humanresources.company-structure.utils',
		'ui.entity-selector',
		'humanresources.company-structure.api',
		'humanresources.company-structure.chart-store',
		'ui.analytics',
		'ui.buttons',
		'ui.forms',
	],
	'skip_core' => false,
];
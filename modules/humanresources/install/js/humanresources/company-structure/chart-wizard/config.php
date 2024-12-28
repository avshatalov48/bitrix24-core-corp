<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/chart-wizard.bundle.css',
	'js' => 'dist/chart-wizard.bundle.js',
	'rel' => [
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.permission-checker',
		'humanresources.company-structure.structure-components',
		'ui.icon-set.api.core',
		'main.core',
		'ui.icon-set.crm',
		'ui.entity-selector',
		'humanresources.company-structure.api',
		'ui.analytics',
		'ui.vue3.pinia',
		'humanresources.company-structure.utils',
		'ui.buttons',
		'ui.forms',
	],
	'skip_core' => false,
];
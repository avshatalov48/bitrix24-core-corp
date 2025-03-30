<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/department-content.bundle.css',
	'js' => 'dist/department-content.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'ui.notification',
		'main.sidepanel',
		'ui.tooltip',
		'ui.icon-set.api.core',
		'humanresources.company-structure.utils',
		'humanresources.company-structure.structure-components',
		'ui.icon-set.crm',
		'ui.icon-set.main',
		'humanresources.company-structure.api',
		'humanresources.company-structure.permission-checker',
		'humanresources.company-structure.user-management-dialog',
		'ui.buttons',
		'humanresources.company-structure.chart-store',
		'ui.vue3.pinia',
	],
	'skip_core' => false,
];

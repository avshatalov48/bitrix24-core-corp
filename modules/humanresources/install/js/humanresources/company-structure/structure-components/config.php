<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/structure-components.bundle.css',
	'js' => 'dist/structure-components.bundle.js',
	'rel' => [
		'main.popup',
		'ui.icon-set.actions',
		'ui.hint',
		'main.core',
		'ui.entity-selector',
		'ui.notification',
		'ui.vue3.pinia',
		'ui.icon-set.api.core',
		'ui.icon-set.api.vue',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.permission-checker',
		'humanresources.company-structure.utils',
		'humanresources.company-structure.api',
	],
	'skip_core' => false,
];
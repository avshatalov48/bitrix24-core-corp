<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/org-chart.bundle.css',
	'js' => 'dist/org-chart.bundle.js',
	'rel' => [
		'ui.vue3',
		'ui.confetti',
		'humanresources.company-structure.canvas',
		'ui.entity-selector',
		'ui.dialogs.messagebox',
		'ui.notification',
		'main.core',
		'main.sidepanel',
		'main.core.events',
		'humanresources.company-structure.api',
		'humanresources.company-structure.department-content',
		'humanresources.company-structure.user-management-dialog',
		'humanresources.company-structure.structure-components',
		'ui.icon-set.api.core',
		'ui.vue3.pinia',
		'ui.icon-set.main',
		'ui.icon-set.crm',
		'ui.buttons',
		'ui.forms',
		'ui.icon-set.api.vue',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.chart-wizard',
		'humanresources.company-structure.utils',
		'ui.analytics',
		'ui.design-tokens',
		'humanresources.company-structure.permission-checker',
	],
	'skip_core' => false,
];
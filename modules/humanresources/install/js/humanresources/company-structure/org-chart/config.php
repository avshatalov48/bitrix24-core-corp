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
		'ui.canvas',
		'humanresources.company-structure.org-chart',
		'ui.entity-selector',
		'ui.dialogs.messagebox',
		'ui.notification',
		'humanresources.company-structure.user-management-dialog',
		'ui.vue3.pinia',
		'humanresources.company-structure.department-content',
		'humanresources.company-structure.api',
		'humanresources.company-structure.structure-components',
		'ui.icon-set.main',
		'ui.icon-set.crm',
		'ui.buttons',
		'ui.forms',
		'ui.icon-set.api.vue',
		'humanresources.company-structure.chart-store',
		'humanresources.company-structure.chart-wizard',
		'ui.analytics',
		'ui.design-tokens',
		'humanresources.company-structure.utils',
		'ui.icon-set.api.core',
		'humanresources.company-structure.permission-checker',
		'main.core.events',
		'main.core',
	],
	'skip_core' => false,
];
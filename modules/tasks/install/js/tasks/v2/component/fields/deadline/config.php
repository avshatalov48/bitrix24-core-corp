<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/deadline.bundle.css',
	'js' => 'dist/deadline.bundle.js',
	'rel' => [
		'ui.system.typography.vue',
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.lib.height-transition',
		'ui.vue3.components.popup',
		'tasks.v2.provider.service.task-service',
		'main.date',
		'ui.date-picker',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.lib.calendar',
		'tasks.v2.lib.timezone',
		'tasks.v2.lib.analytics',
		'main.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];

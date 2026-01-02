<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/task-list.bundle.css',
	'js' => 'dist/task-list.bundle.js',
	'rel' => [
		'main.core',
		'ui.icon-set.outline',
		'main.core.events',
		'main.date',
		'ui.system.typography.vue',
		'ui.icon-set.api.vue',
		'tasks.v2.const',
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.lib.calendar',
		'tasks.v2.provider.service.task-service',
		'tasks.v2.component.fields.responsible',
		'tasks.v2.component.fields.deadline',
		'ui.system.skeleton.vue',
	],
	'skip_core' => false,
];

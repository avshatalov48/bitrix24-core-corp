<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/parent-task.bundle.css',
	'js' => 'dist/parent-task.bundle.js',
	'rel' => [
		'ui.vue3.directives.hint',
		'ui.system.typography.vue',
		'ui.icon-set.api.vue',
		'tasks.v2.component.task-list',
		'tasks.v2.component.elements.hint',
		'tasks.v2.provider.service.task-service',
		'main.core',
		'tasks.v2.core',
		'tasks.v2.lib.relation-error',
		'tasks.v2.provider.service.relation-service',
		'tasks.v2.lib.entity-selector-dialog',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.component.elements.chip',
		'tasks.v2.lib.field-highlighter',
	],
	'skip_core' => false,
];

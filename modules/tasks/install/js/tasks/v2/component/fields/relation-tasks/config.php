<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/relation-tasks.bundle.css',
	'js' => 'dist/relation-tasks.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'ui.vue3.directives.hint',
		'ui.system.typography.vue',
		'ui.icon-set.api.vue',
		'ui.icon-set.actions',
		'tasks.v2.component.task-list',
		'tasks.v2.component.elements.hint',
		'tasks.v2.const',
		'tasks.v2.component.elements.chip',
		'tasks.v2.lib.field-highlighter',
	],
	'skip_core' => true,
];

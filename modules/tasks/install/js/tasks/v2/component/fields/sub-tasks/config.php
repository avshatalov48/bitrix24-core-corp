<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sub-tasks.bundle.css',
	'js' => 'dist/sub-tasks.bundle.js',
	'rel' => [
		'main.core',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.lib.relation-tasks-dialog',
		'tasks.v2.provider.service.relation-service',
		'ui.vue3.directives.hint',
		'tasks.v2.component.elements.hint',
		'tasks.v2.component.fields.relation-tasks',
	],
	'skip_core' => false,
];

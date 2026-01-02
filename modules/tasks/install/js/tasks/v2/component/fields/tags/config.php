<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/tags.bundle.css',
	'js' => 'dist/tags.bundle.js',
	'rel' => [
		'ui.icon-set.api.vue',
		'tasks.v2.component.elements.add-background',
		'tasks.v2.component.elements.field-add',
		'main.core',
		'tasks.entity-selector',
		'tasks.v2.lib.entity-selector-dialog',
		'tasks.v2.core',
		'tasks.v2.provider.service.task-service',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.component.elements.chip',
		'tasks.v2.lib.field-highlighter',
	],
	'skip_core' => false,
];

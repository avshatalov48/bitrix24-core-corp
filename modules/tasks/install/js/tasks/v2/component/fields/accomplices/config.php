<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/accomplices.bundle.js',
	'rel' => [
		'main.core',
		'tasks.v2.core',
		'tasks.v2.provider.service.task-service',
		'ui.entity-selector',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.component.elements.chip',
		'tasks.v2.component.elements.participant-list',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.lib.user-selector-dialog',
	],
	'skip_core' => false,
];

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/relation-tasks-dialog.bundle.js',
	'rel' => [
		'main.core.events',
		'tasks.v2.lib.entity-selector-dialog',
		'tasks.v2.lib.relation-error',
		'tasks.v2.provider.service.task-service',
		'main.core',
		'tasks.v2.const',
		'tasks.v2.provider.service.relation-service',
	],
	'skip_core' => false,
];

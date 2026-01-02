<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/related-tasks.bundle.js',
	'rel' => [
		'main.core',
		'tasks.v2.const',
		'ui.icon-set.api.core',
		'ui.icon-set.actions',
		'tasks.v2.lib.relation-tasks-dialog',
		'tasks.v2.provider.service.relation-service',
		'tasks.v2.component.fields.relation-tasks',
	],
	'skip_core' => false,
];

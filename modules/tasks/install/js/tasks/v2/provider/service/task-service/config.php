<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/task-service.bundle.js',
	'rel' => [
		'tasks.v2.provider.service.check-list-service',
		'main.core',
		'main.core.events',
		'tasks.v2.const',
		'tasks.v2.core',
		'tasks.v2.lib.api-client',
		'tasks.v2.provider.service.file-service',
		'tasks.v2.provider.service.relation-service',
		'tasks.v2.provider.service.group-service',
		'tasks.v2.provider.service.flow-service',
		'tasks.v2.provider.service.user-service',
	],
	'skip_core' => false,
];

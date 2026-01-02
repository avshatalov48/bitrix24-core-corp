<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/pull-manager.bundle.js',
	'rel' => [
		'pull.queuemanager',
		'main.core.events',
		'tasks.v2.core',
		'tasks.v2.provider.service.task-service',
		'tasks.v2.provider.service.relation-service',
		'tasks.v2.provider.service.group-service',
		'tasks.v2.provider.service.flow-service',
		'tasks.v2.provider.service.user-service',
		'main.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];

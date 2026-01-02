<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/status-service.bundle.css',
	'js' => 'dist/status-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'tasks.v2.core',
		'tasks.v2.const',
		'tasks.v2.lib.api-client',
		'tasks.v2.provider.service.task-service',
	],
	'skip_core' => true,
];

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/task-interface-service.bundle.css',
	'js' => 'dist/task-interface-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'tasks.v2.lib.api-client',
	],
	'skip_core' => true,
];

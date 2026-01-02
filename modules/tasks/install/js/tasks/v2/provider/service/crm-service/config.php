<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/crm-service.bundle.css',
	'js' => 'dist/crm-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'tasks.v2.const',
		'tasks.v2.core',
		'tasks.v2.lib.api-client',
	],
	'skip_core' => true,
];

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/importance.bundle.css',
	'js' => 'dist/importance.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.icon-set.api.vue',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.provider.service.task-service',
	],
	'skip_core' => true,
];

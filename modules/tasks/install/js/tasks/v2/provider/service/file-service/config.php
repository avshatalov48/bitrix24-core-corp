<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/file-service.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.uploader.core',
		'ui.uploader.vue',
		'ui.vue3',
		'ui.notification',
		'disk.uploader.user-field-widget',
		'tasks.v2.const',
		'tasks.v2.core',
		'tasks.v2.lib.api-client',
		'main.core',
	],
	'skip_core' => false,
];

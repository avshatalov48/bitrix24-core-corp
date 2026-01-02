<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/drop-zone.bundle.css',
	'js' => 'dist/drop-zone.bundle.js',
	'rel' => [
		'main.core',
		'ui.uploader.core',
		'ui.icon-set.api.vue',
		'ui.icon-set.main',
		'tasks.v2.const',
		'tasks.v2.provider.service.file-service',
	],
	'skip_core' => false,
];

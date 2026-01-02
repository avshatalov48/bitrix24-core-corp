<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/check-list-service.bundle.css',
	'js' => 'dist/check-list-service.bundle.js',
	'rel' => [
		'tasks.v2.model.users',
		'tasks.v2.provider.service.user-service',
		'main.core',
		'ui.vue3.vuex',
		'tasks.v2.const',
		'tasks.v2.core',
		'tasks.v2.lib.api-client',
	],
	'skip_core' => false,
];

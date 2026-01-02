<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/responsible.bundle.css',
	'js' => 'dist/responsible.bundle.js',
	'rel' => [
		'ui.vue3.vuex',
		'ui.icon-set.api.vue',
		'tasks.v2.component.elements.participant',
		'tasks.v2.lib.analytics',
		'tasks.v2.provider.service.task-service',
		'main.core',
		'tasks.v2.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];

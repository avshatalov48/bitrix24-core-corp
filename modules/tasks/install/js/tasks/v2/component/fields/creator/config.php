<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/creator.bundle.js',
	'rel' => [
		'ui.vue3.vuex',
		'tasks.v2.component.elements.participant',
		'tasks.v2.provider.service.task-service',
		'main.core',
		'tasks.v2.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];

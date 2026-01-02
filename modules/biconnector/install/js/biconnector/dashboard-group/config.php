<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dashboard-group.bundle.css',
	'js' => 'dist/dashboard-group.bundle.js',
	'rel' => [
		'ui.vue3',
		'ui.vue3.vuex',
		'main.popup',
		'ui.icon-set.api.core',
		'ui.vue3.components.button',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.icon-set.api.vue',
		'ui.vue3.directives.hint',
	],
	'skip_core' => false,
];

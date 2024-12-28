<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/edit.bundle.css',
	'js' => 'dist/edit.bundle.js',
	'rel' => [
		'ui.buttons',
		'ui.sidepanel',
		'ui.sidepanel.layout',
		'ui.vue3',
		'ui.vue3.vuex',
		'ui.design-tokens',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.loader',
	],
	'skip_core' => false,
];
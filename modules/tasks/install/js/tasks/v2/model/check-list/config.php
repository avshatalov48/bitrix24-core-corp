<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/check-list.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.vuex',
		'tasks.v2.const',
	],
	'skip_core' => true,
];

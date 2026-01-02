<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/vue-util.bundle.css',
	'js' => 'dist/vue-util.bundle.js',
	'rel' => [
		'main.date',
		'ui.date-picker',
		'sign.v2.b2e.sign-dropdown',
		'main.core',
		'ui.vue3.components.rich-menu',
		'ui.icon-set.api.vue',
	],
	'skip_core' => false,
];

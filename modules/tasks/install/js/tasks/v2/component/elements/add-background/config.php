<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/add-background.bundle.css',
	'js' => 'dist/add-background.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.icon-set.api.vue',
		'ui.icon-set.outline',
	],
	'skip_core' => true,
];

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registry.bundle.css',
	'js' => 'dist/registry.bundle.js',
	'rel' => [
		'main.popup',
		'ui.loader',
		'ui.vue3',
		'ui.vue3.components.audioplayer',
		'main.core',
	],
	'skip_core' => false,
];

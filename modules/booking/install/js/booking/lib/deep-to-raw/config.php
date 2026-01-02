<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/deep-to-raw.bundle.css',
	'js' => 'dist/deep-to-raw.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3',
	],
	'skip_core' => false,
];

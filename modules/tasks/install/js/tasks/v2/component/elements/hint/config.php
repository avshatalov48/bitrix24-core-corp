<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hint.bundle.css',
	'js' => 'dist/hint.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.components.popup',
	],
	'skip_core' => false,
];

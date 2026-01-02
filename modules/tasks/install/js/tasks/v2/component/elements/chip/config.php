<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/chip.bundle.css',
	'js' => 'dist/chip.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.system.chip.vue',
	],
	'skip_core' => true,
];

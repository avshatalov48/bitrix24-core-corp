<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/help-desk-loc.bundle.css',
	'js' => 'dist/help-desk-loc.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.components.rich-loc',
	],
	'skip_core' => true,
];

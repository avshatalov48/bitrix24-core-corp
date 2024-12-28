<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/rating.bundle.css',
	'js' => 'dist/rating.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3',
		'ui.ears',
		'main.popup',
		'ui.design-tokens',
	],
	'skip_core' => true,
];